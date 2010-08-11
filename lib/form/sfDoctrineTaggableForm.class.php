<?php

/**
 * Effectively extends the sfForm class by hooking into the
 * form.method_not_found event.
 */
class sfDoctrineTaggableForm
{
  /**
   * The current action class being extended
   *
   * @var sfForm
   */
  protected $_form;

  public function addTagsField()
  {
    if (!($this->_form instanceof sfFormObject))
    {
      throw new sfException('A tags field can only be added to a Doctrine form.');
    }

    if (!$this->_form->getObject()->getTable()->hasTemplate('Taggable'))
    {
      throw new sfException('A tags field can only be added to a form whose model act as "Taggable".');
    }

    // setup the widget
    $config = sfConfig::get('app_sf_doctrine_taggable_widget');
    $class = isset($config['class']) ? $config['class'] : sfWidgetFormInputText();
    $options = isset($config['options']) ? $config['options'] : array();
    $widget = new $class($options);

    $this->_form->setWidget('tags', $widget);

    // setup the validator
    $config = sfConfig::get('app_sf_doctrine_taggable_validator');
    $class = isset($config['class']) ? $config['class'] : sfValidatorString();
    $options = isset($config['options']) ? $config['options'] : array();
    $validator = new $class($options);

    $this->_form->setValidator('tags', $validator);

    // setup the default value
    $this->_form->setDefault('tags', $this->_form->getObject()->getTags(array('serialized' => true)));
  }

  /**
   * Listens to the form.method_not_found event to effectively
   * extend the form class
   */
  public function listenFormMethodNotFound(sfEvent $event)
  {
    $this->_form = $event->getSubject();
    $method = $event['method'];
    $arguments = $event['arguments'];

    if (method_exists($this, $method))
    {
      $result = call_user_func_array(array($this, $method), $arguments);

      $event->setReturnValue($result);

      return true;
    }
    else
    {
      return false;
    }
  }
}