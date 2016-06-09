<?php

namespace Captcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;

class CaptchaComponent extends Component {

    use EventDispatcherTrait;

    /*
     * @var array
     */
    protected $_defaultConfig = [
    ];

    /**
     * Request object
     *
     * @var \Cake\Network\Request
     */
    public $request;

    /**
     * Response object
     *
     * @var \Cake\Network\Response
     */
    public $response;

    /**
     * Initialize properties.
     *
     * @param array $config The config data.
     * @return void
     */
    public function initialize(array $config)
    {
        $controller = $this->_registry->getController();
        $this->eventManager($controller->eventManager());
        $this->response =& $controller->response;
    }

    /**
     * Callback for Controller.startup event.
     *
     * @param \Cake\Event\Event $event Event instance.
     * @return \Cake\Network\Response|null
     */
    public function startup(Event $event)
    {
        return $this->setUpValidation($event);
    }

}
