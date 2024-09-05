<?php
namespace DTApi\Helpers;

use Carbon\Carbon;
use DTApi\Models\Job;
use DTApi\Models\UserMeta;
use DTApi\Models\Language;

class TeHelper
{
    public static function fetchLanguageFromJobId($id)
    {
        $language = Language::findOrFail($id);
        return $language->language;
    }

    public static function getUsermeta($user_id, $key = false)
    {
        $userMeta = UserMeta::where('user_id', $user_id)->first();

        if (!$key) {
            return $userMeta->usermeta()->get()->all();
        }

        $meta = $userMeta->usermeta()->where('key', '=', $key)->first();
        return $meta ? $meta->value : '';
    }

    public static function convertJobIdsInObjs(array $jobs_ids)
    {
        $jobs = [];
        foreach ($jobs_ids as $job_obj) {
            $jobs[] = Job::findOrFail($job_obj->id);
        }
        return $jobs;
    }

    public static function willExpireAt($due_time, $created_at)
    {
        $due_time = Carbon::parse($due_time);
        $created_at = Carbon::parse($created_at);
        $difference = $due_time->diffInHours($created_at);

        if ($difference <= 1.5) {
            return $due_time->format('Y-m-d H:i:s');
        }

        if ($difference <= 24) {
            return $created_at->addMinutes(90)->format('Y-m-d H:i:s');
        }

        if ($difference > 24 && $difference <= 72) {
            return $created_at->addHours(16)->format('Y-m-d H:i:s');
        }

        return $due_time->subHours(48)->format('Y-m-d H:i:s');
    }


}

