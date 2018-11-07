<?php
/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Xml/classes/class.ilSaxParser.php';
require_once 'Modules/Chatroom/classes/class.ilChatroomUser.php';
require_once 'Modules/Chatroom/classes/class.ilChatroom.php';

/**
 * Class ilChatroomXMLParser
 */
class ilChatroomXMLParser extends ilSaxParser
{
	/**
	 * @var ilObjChatroom
	 */
	protected $chat;

	/**
	 * @var ilChatroom
	 */
	protected $room;

	/**
	 * @var null|int
	 */
	protected $import_install_id = null;

	/**
	 * @var string
	 */
	protected $cdata = '';

	/**
	 * @var bool
	 */
	protected $in_sub_rooms = false;

	/**
	 * @var bool
	 */
	protected $in_messages = false;

	/**
	 * @var int|null
	 */
	protected $exportRoomId = 0;

	/**
	 * @var array
	 */
	protected $userIds  = array();

	/**
	 * @var int|null
	 */
	protected $exportSubRoomId  = 0;

	/**
	 * @var int|null
	 */
	protected $owner  = 0;

	/**
	 * @var int|null
	 */
	protected $closed  = 0;

	/**
	 * @var int|null
	 */
	protected $public  = 0;

	/**
	 * @var int|null
	 */
	protected $timestamp = 0;

	/**
	 * @var string|null
	 */
	protected $message = '';

	/**
	 * @var string|null
	 */
	protected $title = '';

	/**
	 * @var array
	 */
	protected $subRoomIdMapping = array();

	/**
	 * Constructor
	 *
	 * @param ilObjChatroom $chat
	 * @param string $a_xml_data
	 */
	public function __construct($chat, $a_xml_data)
	{
		parent::__construct();

		$this->chat = $chat;

		$this->room = ilChatroom::byObjectId($this->chat->getId());
		if(!$this->room)
		{
			$this->room = new ilChatroom();
			$this->room->setSetting('object_id', $this->chat->getId());
			$this->room->save();
		}

		$this->setXMLContent('<?xml version="1.0" encoding="utf-8"?>' . $a_xml_data);
	}

	/**
	 * @param int|null $id
	 */
	public function setImportInstallId($id)
	{
		$this->import_install_id = $id;
	}

	/**
	 * @return int|null
	 */
	public function getImportInstallId()
	{
		return $this->import_install_id;
	}

	/**
	 * @return bool
	 */
	private function isSameInstallation()
	{
		return defined('IL_INST_ID') && IL_INST_ID > 0 && $this->getImportInstallId() == IL_INST_ID;
	}

	/**
	 * @inheritdoc
	 */
	public function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser, $this);
		xml_set_element_handler($a_xml_parser, 'handlerBeginTag', 'handlerEndTag');
		xml_set_character_data_handler($a_xml_parser, 'handlerCharacterData');
	}

	/**
	 * @param $a_xml_parser
	 * @param $a_name
	 * @param $a_attribs
	 */
	public function handlerBeginTag($a_xml_parser, $a_name, $a_attribs)
	{
		switch($a_name)
		{
			case 'SubRooms':
				$this->in_sub_rooms = true;
				break;

			case 'Messages':
				$this->in_messages = true;
				break;
		}
	}

	/**
	 * @param $a_xml_parser
	 * @param $a_name
	 */
	public function handlerEndTag($a_xml_parser, $a_name)
	{
		$this->cdata = trim($this->cdata);

		switch($a_name)
		{
			case 'Title':
				if($this->in_sub_rooms)
				{
					$this->title = $this->cdata;
				}
				else
				{
					$this->chat->setTitle($this->cdata);
				}
				break;

			case 'Description':
				$this->chat->setDescription($this->cdata);
				break;

			case 'OnlineStatus':
				$this->room->setSetting('online_status', (int)$this->cdata);
				break;

			case 'AllowAnonymousAccess':
				$this->room->setSetting('allow_anonymous', (int)$this->cdata);
				break;

			case 'AllowCustomUsernames':
				$this->room->setSetting('allow_custom_usernames', (int)$this->cdata);
				break;

			case 'EnableHistory':
				$this->room->setSetting('enable_history', (int)$this->cdata);
				break;

			case 'RestrictHistory':
				$this->room->setSetting('restrict_history', (int)$this->cdata);
				break;

			case 'PrivateRoomsEnabled':
				$this->room->setSetting('private_rooms_enabled', (int)$this->cdata);
				break;

			case 'DisplayPastMessages':
				$this->room->setSetting('display_past_msgs', (int)$this->cdata);
				break;

			case 'AutoGeneratedUsernameSchema':
				$this->room->setSetting('autogen_usernames', $this->cdata);
				break;

			case 'RoomId':
				$this->exportRoomId = (int)$this->cdata;
				break;
				
			case 'SubRoomId':
				$this->exportSubRoomId = (int)$this->cdata;
				break;

			case 'Owner':
				$this->owner = (int)$this->cdata;
				break;

			case 'Closed':
				$this->closed = (int)$this->cdata;
				break;

			case 'Public':
				$this->public = (int)$this->cdata;
				break;

			case 'CreatedTimestamp':
				$this->timestamp = (int)$this->cdata;
				break;
				
			case 'PrivilegedUserId':
				$this->userIds[] = (int)$this->cdata;
				break;

			case 'SubRoom':
				if($this->isSameInstallation() && $this->exportRoomId > 0)
				{
					$user = new ilObjUser();
					$user->setId($this->owner);

					$chat_user = new ilChatroomUser($user, $this->room);
					$subRoomId = $this->room->addPrivateRoom(
						$this->title, $chat_user, array(
							'public'  => (bool)$this->public,
							'created' => (int)$this->timestamp,
							'closed'  => (bool)$this->closed
						)
					);

					foreach($this->userIds as $userId)
					{
						$this->room->inviteUserToPrivateRoom($userId, $subRoomId);
					}

					$this->subRoomIdMapping[$this->exportRoomId] = $subRoomId;
				}

				$this->exportSubRoomId = 0;
				$this->title           = '';
				$this->owner           = 0;
				$this->closed          = 0;
				$this->public          = 0;
				$this->timestamp       = 0;
				$this->userIds         = array();
				break;

			case 'SubRooms':
				$this->in_sub_rooms = false;
				break;

			case 'Body':
				$this->message = $this->cdata;
				break;

			case 'Message':
				if($this->isSameInstallation())
				{
					$message = json_decode($this->message, true);
					if(
						is_array($message) &&
						(!$this->exportSubRoomId || array_key_exists($this->exportSubRoomId, $this->subRoomIdMapping))
					)
					{
						$message['roomId']    = $this->room->getRoomId();
						$message['subRoomId'] = $this->exportSubRoomId ? $this->subRoomIdMapping[$this->exportSubRoomId] : 0;
						$message['timestamp'] = $this->timestamp;

						$this->room->addHistoryEntry($message);
					}
				}

				$this->timestamp       = 0;
				$this->exportSubRoomId = 0;
				break;

			case 'Messages':
				$this->in_messages = false;
				break;

			case 'Chatroom':
				$this->chat->update();
				// Set imported chats to offline
				$this->room->setSetting('online_status', 0);
				$this->room->save();
				break;
		}

		$this->cdata = '';
	}

	/**
	 * @param $a_xml_parser
	 * @param $a_data
	 */
	public function handlerCharacterData($a_xml_parser, $a_data)
	{
		if($a_data != "\n")
		{
			$this->cdata .= preg_replace("/\t+/"," ",$a_data);
		}
	}
}
