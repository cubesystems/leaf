<?php
interface leafAutomatorActionInterface
{
    public static function getActionDefinitions();
    public static function evaluateAction($data);
}
