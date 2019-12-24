<?php
/** lista cartelelor sim deconectate luna curenta (care au trecut din activ in inactiv).
 *  in tarif trebuie sa fie GSM
 * */
require_once 'ClientChecker.php';
require_once 'PHPMailer-master/PHPMailerAutoload.php';

$navixy = new ClientChecker();

$trackers = $navixy->getTrackerList();

$trackers = array_filter($trackers, function ($tracker) {
    return !$tracker->clone;
});

$last_month_statuses = $navixy->getAllTrackersStatuses();

$deactivated_trackers = [];
$activated_trackers = [];

foreach ($trackers as $tracker) {
    $found_status_index = array_search($tracker->id, array_column($last_month_statuses, 'tracker_id'));

//    if not found index, this tracker is new. Then we add it
    if ($found_status_index === false) {
        $activated_trackers[$tracker->id] = [
            'user_id' => $tracker->user_id,
            'tracker_id' => $tracker->id,
            'phone' => $tracker->source->phone,
            'new' => true
        ];
    } else {

        $found_status = $last_month_statuses[$found_status_index];

        if ($tracker->deleted === true && $found_status['status'] === 'active') {
            $deactivated_trackers[$tracker->id] = [
                'user_id' => $tracker->user_id,
                'tracker_id' => $tracker->id,
                'phone' => $tracker->source->phone,
            ];
        } elseif ($tracker->deleted === false && $found_status['status'] === 'inactive') {
            $activated_trackers[$tracker->id] = [
                'user_id' => $tracker->user_id,
                'tracker_id' => $tracker->id,
                'phone' => $tracker->source->phone,
                'new' => false
            ];

        }

    }

    $navixy->setTrackerStatus($tracker->id, $tracker->deleted);

}

echo "<pre>";
var_dump($activated_trackers);
var_dump($deactivated_trackers);
echo "</pre>";
