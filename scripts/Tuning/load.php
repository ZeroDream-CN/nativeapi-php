<?php
// Import: Callbacks
class Tuning {
    private $ZeroDream;
    private $cb;
    private $logger;
    private $database;

    public function __construct()
    {
        global $logger, $database;
        $this->logger = $logger;
        $this->database = $database;
    }
    
    public function onLoad($Callbacks)
    {
        $this->cb = $Callbacks;
        $this->ZeroDream = (new exports('zerodream_core'))->GetSharedObject();

        RegisterServerEvent('zerodream_tuning:saveVehicleTuning', function ($source, $plate, $data) {
            $xPlayer = $this->ZeroDream->Player->GetData($source);
            $this->logger->info(sprintf('Player %s is saving tuning data for vehicle %s', $xPlayer['name'], $plate));
            $conn    = $this->database->getConnection();
            $result  = $conn->prepare("SELECT * FROM user_vehicles WHERE plate = :plate AND identifier = :identifier");
            $result->execute([
                ':plate'      => $plate,
                ':identifier' => $xPlayer['identifier']
            ]);
            $result = $result->fetchAll(PDO::FETCH_ASSOC);

            if ($result) {
                $vehicle = json_decode($result[0]['data'], true, 512, JSON_PRESERVE_ZERO_FRACTION) ?? [];
                if ($vehicle['model'] !== null && ($vehicle['model'] == $data['model'] || GetHashKey($vehicle['model']) == $data['model'])) {
                    foreach ($data as $k => $v) {
                        $vehicle[$k] = $v;
                    }
                    $update = $conn->prepare("UPDATE user_vehicles SET data = :data WHERE plate = :plate AND identifier = :identifier");
                    $update->execute([
                        ':plate'      => $plate,
                        ':identifier' => $xPlayer['identifier'],
                        ':data'       => json_encode($vehicle, JSON_PRESERVE_ZERO_FRACTION)
                    ]);
                    $this->ZeroDream->Notify->Send($source, "车辆改装自动保存成功");
                } else {
                    $this->ZeroDream->Notify->Send($source, "车辆改装保存失败，数据不匹配");
                }
            } else {
                $this->ZeroDream->Notify->Send($source, "你不是车主，当前改装不会被保存");
            }
        });

        $this->cb->Unregister('zerodream_tuning:confirm');
        $this->cb->Register('zerodream_tuning:confirm', function($source, $cb, $location, $originalModsList, $currentModsList) {
            $xPlayer = $this->ZeroDream->Player->GetData($source);
            $price   = $this->CalculatePrice($originalModsList, $currentModsList, $location);
            if ($price > 0) {
                if ($xPlayer['money'] >= $price) {
                    $this->ZeroDream->Player->RemoveMoney($source, $price, "zerodream_tuning_buyMods");
                    $cb(['success' => true, 'message' => sprintf('改装完成，花费了 %d 元', $price)]);
                } else {
                    $cb(['success' => false, 'message' => '你没有足够的钱']);
                }
            } else {
                $cb(['success' => true, 'message' => '改装完成，车辆没有改动']);
            }
        });
    }

    private function CalculatePrice($originalModsList, $currentModsList, $location) {
        $price = 0;
        for ($i = 0; $i < 59; $i++) {
            if (isset($originalModsList[$i]) && isset($currentModsList[$i])) {
                if ($originalModsList[$i] != $currentModsList[$i]) {
                    $price += 5000;
                }
            }
        }
        $multiplier = isset($location) && isset($location['priceMultiplier']) ? $location['priceMultiplier'] : 1.0;
        return floor($price * $multiplier);
    }
}
