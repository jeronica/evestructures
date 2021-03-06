<?php

namespace App\Notifications\Slack;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Log;

class FractureSlack extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($type, $extraction, $moon, $extraction_data, $character)
    {
      $this->type = $type;
      $this->extraction = $extraction;
      $this->moon = $moon;
      $this->extraction_data = $extraction_data;
      $this->character = $character;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }


    public function toSlack($notifiable) {
      try {
        Log::debug("Sending Fracture slack notification for {$this->moon->name} for character $notifiable->character_id");
        $fracture_data = new \stdClass(); 

        $ping_here = $notifiable->extraction_ping_here == True ? ' <!here>' : '';

        if($this->type == 'manual') {
          $fracture_data->{'message'} = "Expected Manual Fracture"; 
          $fracture_data->{'time'} = $this->extraction->chunk_arrival_time;
          $fracture_data->{'content'} = ":boom: {$this->moon->name} is ready to Manual Fracture for {$this->character->corporation_name}! :boom: $ping_here";
        } else {
          $fracture_data->{'message'} = "Auto Fracture"; 
          $fracture_data->{'time'} = $this->extraction->natural_decay_time;
          $fracture_data->{'content'} = ":boom: {$this->moon->name} is about to Auto Fracture for {$this->character->corporation_name}! :boom: $ping_here";
        }

        return (new SlackMessage)
          ->image(env('APP_URL') . "/images/avatar.png")
          ->content($fracture_data->content)
          ->from(env('APP_NAME') . 'Bot')
          ->attachment(function ($attachment) use ($fracture_data) {
            $attachment->fields([
                $fracture_data->message => $fracture_data->time, 
                'Moon' => $this->moon->name,
                'Ores Available' => $this->extraction_data->ores,
                'Estimated Value' => number_format($this->extraction_data->value),
            ]);
          });
      } catch (\Exception $e) {
        Log::error("Failed to send fracture slack notification for {$this->moon->name} on account $notifiable->user_id , $e");
      }
    }
}
