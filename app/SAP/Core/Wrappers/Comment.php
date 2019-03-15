<?php


namespace App\SAP\Core\Wrappers;

use Carbon\Carbon;
use JsonSerializable;

class Comment implements JsonSerializable
{
    protected $created_on;

    protected $user;

    protected $text = '';

    public function createdOn($date)
    {
        $this->created_on = Carbon::parse($date);
    }

    public function addText($line)
    {
        $this->text .= trim($line) . ' ';
    }

    public function addUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'test'
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'user' => $this->user,
            'text' => $this->text,
            'created_on' => $this->created_on ? $this->created_on->format('l, F jS Y \\@ h:i:s A') : $this->created_on
        ];
    }
}