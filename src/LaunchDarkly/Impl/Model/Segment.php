<?php
namespace LaunchDarkly\Impl\Model;

use LaunchDarkly\LDUser;

/**
 * Internal data model class that describes a user segment.
 *
 * Application code should never need to reference the data model directly.
 */
class Segment
{
    /** @var string */
    protected $_key;
    /** @var int */
    protected $_version;
    /** @var string[] */
    protected $_included = array();
    /** @var string[] */
    protected $_excluded = array();
    /** @var string */
    protected $_salt;
    /** @var SegmentRule[] */
    protected $_rules = array();
    /** @var bool */
    protected $_deleted = false;

    protected function __construct(string $key,
                                   int $version,
                                   array $included,
                                   array $excluded,
                                   string $salt,
                                   array $rules,
                                   bool $deleted)
    {
        $this->_key = $key;
        $this->_version = $version;
        $this->_included = $included;
        $this->_excluded = $excluded;
        $this->_salt = $salt;
        $this->_rules = $rules;
        $this->_deleted = $deleted;
    }

    public function to_object(): object
    {
        return json_decode(json_encode([
            'key' => $this->_key,
            'version' => $this->_version,
            'included' => $this->_included,
            'excluded' => $this->_excluded,
            'salt' => $this->_salt,
            array_map(function ($v) { $v->to_object(); }, $this->_rules),
            'deleted' => $this->_deleted,
        ]));
    }

    public static function getDecoder(): \Closure
    {
        return function (array $v) {
            return new Segment(
                $v['key'],
                $v['version'],
                $v['included'] ?: [],
                $v['excluded'] ?: [],
                $v['salt'],
                array_map(SegmentRule::getDecoder(), $v['rules'] ?: []),
                $v['deleted']
            );
        };
    }

    public static function decode(array $v): Segment
    {
        return static::getDecoder()($v);
    }

    public function matchesUser(LDUser $user): bool
    {
        $key = $user->getKey();
        if (!$key) {
            return false;
        }
        if (in_array($key, $this->_included, true)) {
            return true;
        }
        if (in_array($key, $this->_excluded, true)) {
            return false;
        }
        foreach ($this->_rules as $rule) {
            if ($rule->matchesUser($user, $this->_key, $this->_salt)) {
                return true;
            }
        }
        return false;
    }

    public function getVersion(): ?int
    {
        return $this->_version;
    }

    public function getKey(): string
    {
        return $this->_key;
    }

    public function isDeleted(): bool
    {
        return $this->_deleted;
    }
}
