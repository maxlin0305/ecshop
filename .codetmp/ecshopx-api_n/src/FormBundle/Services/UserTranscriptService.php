<?php

namespace FormBundle\Services;

use FormBundle\Entities\UserTranscripts;

class UserTranscriptService
{
    public $userTranscriptsRepository;

    public function __construct()
    {
        $this->userTranscriptsRepository = app('registry')->getManager('default')->getRepository(UserTranscripts::class);
    }

    public function createUserTranscript($params)
    {
        $data = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
            'shop_id' => isset($params['shop_id']) ? $params['shop_id'] : '',
            'transcript_id' => $params['transcript_id'],
            'transcript_name' => $params['transcript_name'],
            'indicator_details' => $params['indicator_details'],
        ];

        return $this->userTranscriptsRepository->create($data);
    }

    public function getUserTranscript($filter)
    {
        $result = $this->userTranscriptsRepository->list($filter);

        return $result;
    }

    public function getUserTranscriptByRecordId($record_id)
    {
        $userTranscript = $this->userTranscriptsRepository->get($record_id);
        $result = [
            'record_id' => $userTranscript->getRecordId(),
            'user_id' => $userTranscript->getUserId(),
            'company_id' => $userTranscript->getCompanyId(),
            'shop_id' => $userTranscript->getShopId(),
            'transcript_id' => $userTranscript->getTranscriptId(),
            'transcript_name' => $userTranscript->getTranscriptName(),
            'indicator_details' => $userTranscript->getIndicatorDetails(),
            'created' => $userTranscript->getCreated(),
            'updated' => $userTranscript->getUpdated(),
        ];

        return $result;
    }
}
