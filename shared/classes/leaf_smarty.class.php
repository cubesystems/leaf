<?php
// load Smarty library
require(SHARED_PATH . '3rdpart/smarty/Smarty.class.php');

class leaf_smarty extends Smarty
{

   function leaf_smarty($template_dir)
   {
        $this->Smarty();
        $this->compile_id = 'smartycache_' . substr(md5($template_dir),5);
        
        $config = leaf_get('properties', 'smarty');
        
        if (
            (!$config)
            ||
            (!isset($config['plugins_dir']))
        )
        {
            $pluginsDir = array(
                SHARED_PATH . 'classes/smarty_plugins',
                'plugins' // the default under SMARTY_DIR
            );
        }
        else
        {
            $pluginsDir = $config['plugins_dir'];
        }

        $this->plugins_dir = $pluginsDir;

        $this->template_dir = $template_dir;
        $this->compile_dir = CACHE_PATH;
        $this->cache_dir = CACHE_PATH;
   }

}
?>