<?php
// 1. INCLUSIÓN OBLIGATORIA
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

// 2. EXTENSIÓN OBLIGATORIA (extends DolibarrModules)
class modWarehouseFilter extends DolibarrModules {
    public function __construct($db) {
        global $conf;
        
        $this->db = $db;
        $this->numero = 500000; 
        $this->name = "WarehouseFilter";
        $this->description = "Filtra el stock por almacén en el listado de productos";
        $this->family = "other";
        $this->version = '1.04';
        $this->const_name = 'MAIN_MODULE_WAREHOUSEFILTER';
        $this->enabled = 1;
        
        // Incluimos ambos contextos posibles por seguridad
        $this->module_parts = array('hooks' => array('productlist', 'productservicelist'));
        
        // 3. DIRECTORIO DE CLASES OBLIGATORIO (Para que encuentre el archivo actions)
        $this->dirs = array("/warehousefilter/class");
    }
}
