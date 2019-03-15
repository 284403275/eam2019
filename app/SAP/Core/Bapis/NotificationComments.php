<?php


namespace App\SAP\Core\Bapis;


use App\SAP\Core\SAP;
use App\SAP\Core\Wrappers\Comment;
use App\Services\GlobalUser;

class NotificationComments extends SAP
{
    protected $comments;

    protected $lastComment;

    protected $globalUser;

    public function __construct()
    {
        parent::__construct();

        $this->globalUser = new GlobalUser();
    }

    public function find($notification)
    {
        $this->parse($this->fm('Z_UI5_8FWEX_GET_NTF_LONGTEXT', [
            'IV_NOTIFICATION' => $notification
        ])->addParameter('IV_NOTIFICATION', $notification)->invoke());

        return $this->comments;
    }

    public function parse($raw)
    {
        $this->lastComment = new Comment();

        $numLines =  collect($raw['ET_LONGTEXT_LIST'])->count();

        collect($raw['ET_LONGTEXT_LIST'])->map(function ($item) use ($numLines) {
            //Find the date in the comment line
            preg_match('/(\d{2}|\d{4})(-|\/)(\d{2})(-|\/)(\d{4}|\d{2}) (\d{2}):(\d{2}):(\d{2})/', $item['COMMENT_LINE'], $created);

            //If a date exists
            if(isset($created[0])) {
                if($item['LINE_NO'] > 1) {
                    $this->comments[] = $this->lastComment;
                    $this->lastComment = new Comment();
                }
                //Check if there is a user
                preg_match('/\((.*?)\)/', $item['COMMENT_LINE'], $user);

                $this->lastComment->addUser($this->globalUser->findOrCreateByAccount($user[1])->only(['account', 'first_name', 'last_name', 'email', 'avatar']));

                $this->lastComment->createdOn($created[0]);
            } else {
                preg_match('/^(.)\1{5,}/', $item['COMMENT_LINE'], $break);
                if(!isset($break[0]))
                    $this->lastComment->addText(trim($item['COMMENT_LINE']));
            }

            if($item['LINE_NO'] == $numLines) {
                $this->comments[] = $this->lastComment;
            }
        });
    }
}