<?php
$ZeroDream = (new exports('zerodream_core'))->GetSharedObject();

RegisterServerEvent('zerodream_tuning:saveVehicleTuning', function ($source, $plate, $data) use ($ZeroDream) {
    global $logger, $database;
    $xPlayer = $ZeroDream->Player->GetData($source);
    $logger->info(sprintf('Player %s is saving tuning data for vehicle %s', $xPlayer['name'], $plate));
    $conn    = $database->getConnection();
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
            $ZeroDream->Notify->Send($source, "Vehicle tuning saved successfully");
        } else {
            $ZeroDream->Notify->Send($source, "Failed to save vehicle tuning, model mismatch");
        }
    } else {
        $ZeroDream->Notify->Send($source, "You are not the owner of this vehicle");
    }
});
