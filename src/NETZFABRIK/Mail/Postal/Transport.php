<?php

namespace NETZFABRIK\Mail\Postal;

use \Swift_Events_EventListener;
use \Swift_Mime_MimePart;
use \Swift_Mime_SimpleMessage;
use \Swift_Transport;


class Transport implements Swift_Transport {

    /**
	 * The Postal API key.
	 *
	 * @var string
	 */
    protected $apiKey;

    /**
	 * The Postal Server url.
	 *
	 * @var string
	 */
    protected $serverUrl;
    

    /**
     * The event dispatcher from the plugin API.
     *
     * @var \Swift_Events_EventDispatcher eventDispatcher
     */
    protected $_eventDispatcher;
    

    /**
	 * Create a new Postmark transport instance.
	 *
	 * @param  string  $serverToken The API token for the server from which you will send mail.
	 * @return void
	 */
	public function __construct($serverUrl, $apiKey) {
		$this->serverUrl = $serverUrl;
		$this->apiKey = $apiKey;
		$this->_eventDispatcher = \Swift_DependencyContainer::getInstance()->lookup('transport.eventdispatcher');
    }
    
    /**
     * Not used.
     */
	public function isStarted() {
		return true;
    }
    
	/**
     * Not used.
     */
	public function start() {
		return true;
    }
    
	/**
     * Not used.
     */
	public function stop() {
		return true;
	}
	/**
	 * Not used
	 *
	 * @return bool
	 */
	public function ping() {
		return true;
    }
    
    /**
	 * Send the given Message.
     * 
     * @param Swift_Mime_SimpleMessage $message
     * @param string[]           $failedRecipients An array of failures by-reference
     * 
     * @throws \Swift_TransportException
     * 
     * @return int number of mails sent
	 */
	public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null) {
        $client = new \Postal\Client($this->serverUrl, $this->apiKey);
		if ($sendEvent = $this->_eventDispatcher->createSendEvent($this, $message)) {
			$this->_eventDispatcher->dispatchEvent($sendEvent, 'beforeSendPerformed');
			if ($sendEvent->bubbleCancelled()) {
				return 0;
			}
        }
        
        if (null === $message->getTo()) {
            throw new \Swift_TransportException('Cannot send message without a recipient');
        }

        try {
            $postalMessage = new \Postal\SendMessage($client);

            // Specify who the message should be from. This must be from a verified domain
            // on your mail server.
            $postalMessage->from($message->getHeaders()->get('From'));
            $postalMessage->to($message->getTo());
            $postalMessage->subject($message->getHeaders()->get('Subject'));
            $postalMessage->header('X-Complaints-To', 'abuse@netzfabrik.com');
            $postalMessage->header('X-Mailer', 'NETZFABRIK Postal PHP Package');
            $postalMessage->plainBody($message->toString());
            $postalResult = $postalMessage->send();
            $resultStatus = Swift_Events_SendEvent::RESULT_SUCCESS;

        } catch (\Exception $e) {
            $failedRecipients = $message->getTo();
            $sent = 0;
            $resultStatus = Swift_Events_SendEvent::RESULT_FAILED;
        }

		$success = $response->getStatusCode() === 200;
		if ($responseEvent = $this->_eventDispatcher->createResponseEvent($this, $response->getBody()->getContents(), $success)) {
			$this->_eventDispatcher->dispatchEvent($responseEvent, 'responseReceived');
		}
		if ($sendEvent) {
			$sendEvent->setResult($success ? \Swift_Events_SendEvent::RESULT_SUCCESS : \Swift_Events_SendEvent::RESULT_FAILED);
			$this->_eventDispatcher->dispatchEvent($sendEvent, 'sendPerformed');
		}
		
		return $success
			? $this->getRecipientCount($message)
			: 0;
    }

    /**
	 * Get the number of recipients for a message
	 *
	 * @param Swift_Mime_SimpleMessage $message
	 * @return int
	 */
	private function getRecipientCount(Swift_Mime_SimpleMessage $message) {
	    return count(array_merge(
            (array) $message->getTo(),
            (array) $message->getCc(),
            (array) $message->getBcc())
        );
    }

    /**
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->dispatcher->bindEventListener($plugin);
    }
    
    /**
	 * TBD
	 *
	 * @return string
	 */
	public function getApiKey() {
		return $this->apiKey;
    }
    
    /**
	 * TBD
	 *
	 * @return string
	 */
	public function getServerUrl() {
		return $this->serverUrl;
    }
    
    /**
	 * TBD
	 *
	 * @param  string  $serverToken
	 * @return void
	 */
	public function setApiKey($apiKey) {
		return $this->apiKey = $apiKey;
    }
    
    /**
	 * TBD
	 *
	 * @param  string  $serverToken
	 * @return void
	 */
	public function setServerUrl($serverUrl) {
		return $this->serverUrl = $serverUrl;
	}

}