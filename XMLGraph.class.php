<?php
/**
 * Direct access check
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/**
 * XML Generation for SugarCRM 5.5 Charts
 * As per conventions all comments are stripped ......
 * @author Jeremy Hutchings <email@jeremyhutchings.com>
 */
class XMLGraph
{
    protected $xml  = null;
    
    protected $data = null;
    
    private $sortList = null;

    private $yStep = 10;

    private $yLog = 1;

    public $yOverRide = null;
    
    function __construct($options = array()) 
    {
        $this->xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sugarcharts version="1.0"></sugarcharts>');
        
        // Data place holder
        $this->data = $this->xml->addChild('data');
        
        // Set up the default properties 
        $properties = $this->xml->addChild('properties');
        
                // TYPE - 'group by chart' - 'bar chart' - 'horizontal bar chart' - 'pie chart' 
        if (isset($options['properties']))
        {
            foreach ($options['properties'] AS $key => $value)
            {
                $properties->addChild($key, $value);
            }
        }

        if (isset($options['ystep']))
        {
            $this->yStep = $options['ystep'];
        }
    }
    
    public function asXML()
    {
        $this->buildYAxis();
        return $this->xml->asXML();
    }
    
    
    public function returnXML()
    {
        $this->buildYAxis();
        $this->xml->asXML();
    }
    
    public function addData($group = array())
    {
        $subgroups = null;
        $newData   = $this->data->addChild('group');
        $subgroups = $newData->addChild('subgroups'); 

        foreach ($group AS $key => $value)
        {
            if ($key != 'subgroups')
            {
                $newData->addChild($key, $value);
            }
        }       
        
        // If there are sub groups, sort them out before looping the main array
        if (isset($group['subgroups']))
        {
            foreach ($group['subgroups'] AS $id => $groupArray)
            {
                $innerGroup = $subgroups->addChild('group'); 

                foreach ($groupArray AS $key => $value)
                {
                    $innerGroup->addChild($key, $value);
                }
            }
        }
    }
    
    public function emptyXML()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
                <sugarcharts version="1.0">
                    <properties>
                        <title></title>
                        <subtitle>Empty</subtitle>
                        <type>stacked group by chart</type>
                        <legend>on</legend>
                        <labels>value</labels>
                    </properties>
                    <data>
                    </data>
                    <yAxis>
                        <yMin>0</yMin>
                        <yMax>1</yMax>
                        <yStep>1</yStep>
                        <yLog>1</yLog>
                    </yAxis>
                </sugarcharts>';
    }
    
    private function buildYAxis()
    {
        $yAxis = $this->xml->addChild('yAxis');
        
        $yAxis->addChild('yMin',  $this->getYMin());
        $yAxis->addChild('yMax',  $this->getYMax());
        $yAxis->addChild('yStep', $this->getYStep());
        $yAxis->addChild('yLog',  $this->getYLog());
    } 
    
    private function yRange()
    {
        // Have we aready done it ?
        if (!count($this->sortList))
        {
            foreach($this->xml->xpath('data/group/value') AS $pos => $obj)
            {
                $this->sortList[] = (int) $obj[0];
            }    
        }
    }
    
    private function getYMin()
    {
        $this->yRange();
        return min($this->sortList);
    }
    
    private function getYMax()
    {
              if (isset($this->yOverRide))
              {
                 return $this->yOverRide;        
              }

              $this->yRange();
              return max($this->sortList);
    }
    
    private function getYStep()
    {
              // TODO: Just divides by ten ...... redo
              // or using stepping from some where else ....
              $this->yStep = round($this->getYMax() / 10);
          return $this->yStep;
    }
    
    private function getYLog()
    {
        return $this->yLog;
    }
}






