<?php namespace Ikas\Parser\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Ikas\Parser\Models\CryptowatchData;

class CryptowatchController extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $parsePageUrl = 'https://api.cryptowat.ch/markets/prices';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Ikas.Parser', 'parser');
    }

    public function parse(){
        try{
            $data = json_decode(file_get_contents($this->parsePageUrl), true);
            if (!empty($data['result'])){
                $prepareData = $this->prepareData($data['result']);
                $this->saveData($prepareData);
            }
        } catch (\Exception $e){
            dd($e->getMessage());
        }
        return redirect()->back();
    }

    public function prepareData($data){
        $dataForSave = [];
        foreach ($data as $key => $price){
            $exchangePair = explode(':', $key);
            $row = [
                'exchange' => $exchangePair[0],
                'currency_pair' => $exchangePair[1],
                'price' => $price
            ];
            $dataForSave[] = $row;
        };
      return $dataForSave;
    }

    public function saveData($data){
        foreach ($data as $item){
            $findRow = CryptowatchData::where('exchange', $item['exchange'])->where('currency_pair', $item['currency_pair']);
            if(empty($findRow->get()->toArray())){
                $cryptowatch = new CryptowatchData();
                $cryptowatch->exchange = $item['exchange'];
                $cryptowatch->currency_pair = $item['currency_pair'];
                $cryptowatch->price = $item['price'];
                $cryptowatch->save();
            } else {
                $findRow->update(['price' => $item['price']]);
            };
        }
    }

}