<?php
namespace LaunchDarkly\Tests\Impl\Model;

use LaunchDarkly\FeatureRequester;
use LaunchDarkly\Impl\Model\FeatureFlag;
use LaunchDarkly\Impl\Model\Segment;

class MockFeatureRequesterForFeature implements FeatureRequester
{
    public $key = null;
    public $val = null;

    public function __construct($baseurl = null, $key = null, $options = null)
    {
    }

    public function getFeature(string $key): ?FeatureFlag
    {
        return ($key == $this->key) ? $this->val : null;
    }

    public function getSegment(string $key): ?Segment
    {
        return null;
    }

    public function getAllFeatures(): ?array
    {
        return null;
    }
}
