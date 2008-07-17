<?php
class OA_Admin_Section_Type_Filter 
{
  var $oCurrentSection;
  
  function OA_Admin_Section_Type_Filter($oCurrentSection)
  {
  	$this->oCurrentSection = $oCurrentSection;
  }
  
  
  function accept($oSection) 
  {
  	$currentId = $this->oCurrentSection->getId();
  	
  	//if section is affixed show it only if it's active
    if ($oSection->isAffixed() || $oSection->isExclusive()) {
    	return $oSection->getId() == $currentId;
    }
    
    //filter out other sections if current is exclusive
    return !$this->oCurrentSection->isExclusive();  
  }
}
?>