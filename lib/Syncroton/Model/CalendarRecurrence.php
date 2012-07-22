<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync event
 *
 * @package     Model
 * @property    string  class
 * @property    string  collectionId
 * @property    bool    deletesAsMoves
 * @property    bool    getChanges
 * @property    string  syncKey
 * @property    int     windowSize
 */

class Syncroton_Model_CalendarRecurrence extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Recurrence';
    
    /**
     * recur types
     */
    const RECUR_TYPE_DAILY          = 0;     // Recurs daily.
    const RECUR_TYPE_WEEKLY         = 1;     // Recurs weekly
    const RECUR_TYPE_MONTHLY        = 2;     // Recurs monthly
    const RECUR_TYPE_MONTHLY_DAYN   = 3;     // Recurs monthly on the nth day
    const RECUR_TYPE_YEARLY         = 5;     // Recurs yearly
    const RECUR_TYPE_YEARLY_DAYN    = 6;     // Recurs yearly on the nth day
    
    /**
     * day of week constants
     */
    const RECUR_DOW_SUNDAY      = 1;
    const RECUR_DOW_MONDAY      = 2;
    const RECUR_DOW_TUESDAY     = 4;
    const RECUR_DOW_WEDNESDAY   = 8;
    const RECUR_DOW_THURSDAY    = 16;
    const RECUR_DOW_FRIDAY      = 32;
    const RECUR_DOW_SATURDAY    = 64;
        
    // @todo handle body
    protected $_properties = array(
        'Calendar' => array(
            'CalendarType'            => array('type' => 'number'),
            'DayOfMonth'              => array('type' => 'number'),
            'DayOfWeek'               => array('type' => 'number'),
            'FirstDayOfWeek'          => array('type' => 'number'),
            'Interval'                => array('type' => 'number'),
            'IsLeapMonth'             => array('type' => 'number'),
            'MonthOfYear'             => array('type' => 'number'),
            'Occurrences'             => array('type' => 'number'),
            'Type'                    => array('type' => 'number'),
            'Until'                   => array('type' => 'datetime'),
            'WeekOfMonth'             => array('type' => 'number'),
        )
    );
    
    public function appendXML(DOMElement $_domParrent)
    {
        $_domParrent->ownerDocument->documentElement->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:Calendar', 'uri:Calendar');
        
        foreach($this->_elements as $elementName => $value) {
            // skip empty values
            if($value === null || $value == '' || (is_array($value) && empty($value))) {
                continue;
            }
            
            $elementProperties = $this->_properties['Calendar'][$elementName]; 
            
            $nameSpace = 'uri:Calendar';
            
            // strip off any non printable control characters
            if (!ctype_print($value)) {
                #$value = $this->removeControlChars($value);
            }
            
            switch($elementName) {
                default:
                    $element = $_domParrent->ownerDocument->createElementNS($nameSpace, $elementName);
                    
                    if ($value instanceof DateTime) {
                        $value = $value->format("Ymd\THis\Z");
                    }
                    $element->appendChild($_domParrent->ownerDocument->createTextNode($value));
                    
                    $_domParrent->appendChild($element);
            }
        }
        
    }
    
    /**
     * 
     * @param SimpleXMLElement $xmlCollection
     * @throws InvalidArgumentException
     */
    public function setFromSimpleXMLElement(SimpleXMLElement $properties)
    {
        if ($properties->getName() !== $this->_xmlBaseElement) {
            throw new InvalidArgumentException('Unexpected element name: ' . $properties->getName());
        }
        
        $this->_elements = array();
        
        foreach (array_keys($this->_properties) as $namespace) {
            $functionName = '_parse' . $namespace . 'Namespace';
            $this->$functionName($properties);
        }
        
        $airSyncBaseData = $properties->children('uri:AirSyncBase');
        
        return;
    }
    
    protected function _parseCalendarNamespace(SimpleXMLElement $properties)
    {
        // fetch data from Contacts namespace
        $children = $properties->children('uri:Calendar');
    
        foreach ($children as $elementName => $xmlElement) {
    
            switch ($elementName) {
                default:
                    $properties =  $this->_properties['Calendar'][$elementName];
                    
                    switch ($properties['type']) {
                        case 'datetime':
                            $this->$elementName = new DateTime((string) $xmlElement, new DateTimeZone('UTC'));
                            
                            break;
                            
                        case 'number':
                            $this->$elementName = (int) $xmlElement;
                            
                            break;
                        default:
                            $this->$elementName = (string) $xmlElement;
                            
                            break;
                    }
            }
        }
    }
    
    public function &__get($name)
    {
        if (!array_key_exists($name, $this->_properties['Calendar'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        return $this->_elements[$name];
    }
    
    public function __set($name, $value)
    {
        if (!array_key_exists($name, $this->_properties['Calendar'])) {
            throw new InvalidArgumentException("$name is no valid property of this object");
        }
        
        $properties = $this->_properties['Calendar'][$name];
        
        if ($properties['type'] == 'datetime' && !$value instanceof DateTime) {
            throw new InvalidArgumentException("value for $name must be an instance of DateTime");
        }
        
        $this->_elements[$name] = $value;
    }
}