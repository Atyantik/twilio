<?php

namespace NotificationChannels\Twilio\Test;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Mockery;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioCallMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use PHPUnit_Framework_TestCase;
use NotificationChannels\Twilio\Twilio;

class TwilioChannelTest extends PHPUnit_Framework_TestCase
{
    /** @var TwilioChannel */
    protected $channel;

    /** @var Twilio */
    protected $twilio;

    /** @var Dispatcher */
    protected $dispatcher;

    public function setUp()
    {
        parent::setUp();

        $this->twilio = Mockery::mock(Twilio::class);
        $this->dispatcher = Mockery::mock(Dispatcher::class);

        $this->channel = new TwilioChannel($this->twilio, $this->dispatcher);
    }

    /** @test */
    public function it_will_not_send_a_message_without_known_receiver()
    {
        $notifiable = new Notifiable();
        $notification = Mockery::mock(Notification::class);

        $this->dispatcher->shouldReceive('fire')
            ->atLeast()->once()
            ->with(Mockery::type(NotificationFailed::class));

        $result = $this->channel->send($notifiable, $notification);

        $this->assertNull($result);
    }

    /** @test */
    public function it_will_send_a_sms_message_to_the_result_of_the_route_method_of_the_notifiable()
    {
        $notifiable = new NotifiableWithMethod();
        $notification = Mockery::mock(Notification::class);

        $message = new TwilioSmsMessage('Message text');
        $notification->shouldReceive('toTwilio')->andReturn($message);

        $this->twilio->shouldReceive('sendMessage')
            ->atLeast()->once()
            ->with($message, '+1111111111');

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_will_make_a_call_to_the_phone_number_attribute_of_the_notifiable()
    {
        $notifiable = new NotifiableWithAttribute();
        $notification = Mockery::mock(Notification::class);

        $message = new TwilioCallMessage('http://example.com');
        $notification->shouldReceive('toTwilio')->andReturn($message);

        $this->twilio->shouldReceive('sendMessage')
            ->atLeast()->once()
            ->with($message, '+22222222222');

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_will_convert_a_string_to_a_sms_message()
    {
        $notifiable = new NotifiableWithAttribute();
        $notification = Mockery::mock(Notification::class);

        $notification->shouldReceive('toTwilio')->andReturn('Message text');

        $this->twilio->shouldReceive('sendMessage')
            ->atLeast()->once()
            ->with(Mockery::type(TwilioSmsMessage::class), Mockery::any());

        $this->channel->send($notifiable, $notification);
    }

    /** @test */
    public function it_will_fire_an_event_in_case_of_an_invalid_message()
    {
        $notifiable = new NotifiableWithAttribute();
        $notification = Mockery::mock(Notification::class);

        // Invalid message
        $notification->shouldReceive('toTwilio')->andReturn(-1);

        $this->dispatcher->shouldReceive('fire')
            ->atLeast()->once()
            ->with(Mockery::type(NotificationFailed::class));

        $this->channel->send($notifiable, $notification);
    }
}

class Notifiable
{
    public $phone_number = null;

    public function routeNotificationFor()
    {
    }
}

class NotifiableWithMethod
{
    public function routeNotificationFor()
    {
        return '+1111111111';
    }
}

class NotifiableWithAttribute
{
    public $phone_number = '+22222222222';

    public function routeNotificationFor()
    {
    }
}
