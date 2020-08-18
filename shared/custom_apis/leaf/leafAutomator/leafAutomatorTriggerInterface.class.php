<?php
interface leafAutomatorTriggerInterface
{
    public static function getTriggerDefinition();
    public static function evaluateTrigger($data);
}
