<?php
include_once 'Models/momentConModel.php';

class moentoController{
    private $model;

    public function __construct(){
        $this->model = new MomentConModel();
    }

    public function listaMomentos(){
        return $this->model->getMomento();
    }
}
?>