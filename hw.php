<?php

namespace game;

abstract class BaseClass {
    public function __get(string $name) :mixed {
        return property_exists($this, $name) && isset($this->$name) ? $this->$name : null;
    }
    public function __set(string $name, $value) :void {
        $methodName = 'set' . ucfirst($name);
        if (method_exists($this, $methodName)) {
            $this->$methodName($value);
        }
    }
    public function __call(string $name, array $arguments) :mixed {
        if (str_starts_with($name, 'get')) {
            $propertyName = lcfirst(substr($name, 3));
            return $this->$propertyName;
        }
        return null;
    }
}

/**
 * @property bool   $status
 * @property int    $maxHp
 * @property int    $hp
 * @property string $statusMessageAlive
 * @property string $statusMessageDied
 * @property bool   $canBeRevived
 */
class State extends BaseClass {
    const LIMIT_HP = 10000;

    protected bool   $status;
    protected int    $maxHp;
    protected int    $hp;
    protected string $statusMessageAlive;
    protected string $statusMessageDied;
    protected bool   $canBeRevived;

    public function __construct(int $maxHp = 100, bool $status = true, $canBeRevived = false) {
        $this->setMaxHp($maxHp);
        $this->status = $status;
        $this->setHp($status ? $maxHp : 0);
        $this->canBeRevived = $canBeRevived;
    }
    public function increase(int $points) :bool {
        return $this->setHp($this->hp + $points);
    }
    public function decrease(int $points) :bool {
        return $this->setHp($this->hp - $points);
    }
    public function setHp(int $points) :bool {
        if ($points > $this->maxHp) {
            $points = $this->maxHp;
        } elseif ($points < 0) {
            $points = 0;
        }
        if (!isset($this->hp) || $this->hp !== 0 || $this->canBeRevived) {
            $this->hp = $points;
        }
        $newStatus = $this->hp !== 0;
        if ($newStatus !== $this->status) {
            if ($newStatus && $this->statusMessageAlive) {
                print_r($this->statusMessageAlive . PHP_EOL);
            }
            if (!$newStatus && $this->statusMessageDied) {
                print_r($this->statusMessageDied . PHP_EOL);
            }
        }
        return $this->status = $newStatus;
    }
    public function setMaxHp(int $points) :void {
        if ($points > self::LIMIT_HP) {
            $this->maxHp = self::LIMIT_HP;
        } elseif ($points < 0) {
            $this->maxHp = 0;
        } else {
            $this->maxHp = $points;
        }
    }
    public function setMessageAlive(string $message) :void {
        $this->statusMessageAlive = $message;
    }
    public function setMessageDied(string $message) :void {
        $this->statusMessageDied = $message;
    }
}

/**
 * @property string $name
 * @property string $type
 * @property State  $state
 * @property int    $protection
 */
abstract class Unit extends BaseClass {
    const LIMIT_PROTECTION = 90;

    protected string $name;
    protected string $type;
    protected State  $state;
    protected int    $protection;

    public function __construct(string $name, int $maxHp, int $protection = 0, bool $status = true) {
        $this->type  = static::class;
        $this->name  = $name;
        $this->state = new State($maxHp, $status);
        $this->setProtection($protection);
    }
    public function setProtection(int $points) :void {
        if ($points < 0) {
            $this->protection = 0;
        } elseif ($points > self::LIMIT_PROTECTION) {
            $this->protection = self::LIMIT_PROTECTION;
        } else {
            $this->protection = $points;
        }
    }
    protected function getNameType() :string {
        return "$this->name ($this->type)";
    }
    protected function getAction(string $action) :string {
        return $this->getNameType() . " $action";
    }
    public function getInfo() :string {
        $state     = $this->state;
        $stateInfo = $state->status ? "$state->hp HP" : $state->statusMessageDied;
        return "$this->name ($this->type) [$stateInfo]";
    }
}

/**
 * @property int $force
 */
abstract class Human extends Unit {
    const LIMIT_FORCE = 5000;

    protected int $force;

    public function __construct(
        string $name,
        int    $force = 1,
        int    $protection = 0,
        int    $maxHp = 100,
        bool   $status = true
    ) {
        parent::__construct($name, $maxHp, $protection, $status);
        $this->setForce($force);
        $this->state->setMessageAlive("$this->name снова среди живых!");
        $this->state->setMessageDied("$this->name погиб! как так?!");
    }
    public function setForce(int $points) :void {
        if ($points < 1) {
            $this->force = 1;
        } elseif ($points > self::LIMIT_FORCE) {
            $this->force = self::LIMIT_FORCE;
        } else {
            $this->force = $points;
        }
    }
    public function attack(Unit $unit) :void {
        $attackPoints = round($this->force / 100 * $unit->protection);
        $unitAlive    = $unit->state->decrease($attackPoints);
        print_r(implode(" ", [
            $this->getAction("наносит удар"),
            $unit->getAction("на $attackPoints"),
            PHP_EOL,
        ]));
        if ($unitAlive) {
            print_r($unit->getAction("остался жив!") . PHP_EOL);
        }
    }
}

/**
 * @property int $healPoints
 */
class Wizard extends Human {
    protected int $healPoints = 15;

    public function __construct(string $name, bool $status = true) {
        parent::__construct($name, 50, 20, 100, $status);
    }
    public function heal(Unit $unit) :void {
        if ($unit->state->increase($this->healPoints)) {
            print_r(implode(" ", [
                $this->getAction("лечит"),
                $unit->getAction("на $this->healPoints"),
                PHP_EOL,
            ]));
        } else {
            print_r(implode(" ", [
                $this->getAction("не удалось вылечить"),
                $unit->getNameType(),
                PHP_EOL,
            ]));
        }
    }
}

class Archer extends Human {
    public function __construct(string $name, bool $status = true) {
        parent::__construct($name, 100, 10, 100, $status);
    }
}

class Warrior extends Human {
    public function __construct(string $name, bool $status = true) {
        parent::__construct($name, 20, 50, 200, $status);
    }
}

class Guild extends BaseClass {
    static protected array $guilds = [];
    static public function add(string $name, Human ...$members) :Guild {
        return new self($name, ...$members);
    }
    static public function viewInfo() :void {
        $info = [];
        /** @var self $guild */
        foreach (self::$guilds as $guild) {
            $info[$guild->name] = $guild->getMembers();
        }
        print_r($info);
    }

    protected string $name;
    /** @var Human[] */
    protected array $members;

    public function __construct(string $name, Human ...$humans) {
        $this->name     = $name;
        $this->members  = $humans;
        self::$guilds[] = $this;
    }
    public function getMember(int $id = null) :Human {
        $count = count($this->members);
        if ($id === null || $id < 0 || $id >= $count) {
            $id = rand(0, $count - 1);
        }
        return $this->members[$id];
    }
    public function addMember(Human $member) :void {
        $this->members[] = $member;
    }
    public function getMembers() :array {
        $members = [];
        foreach ($this->members as $member) {
            $members[] = $member->getInfo();
        }
        return $members;
    }
}

// Тут балуемся

$guildRed  = new Guild(
    'Red',
    new Wizard('Волшебник 1'),
    new Warrior('Воин 1'),
    new Warrior('Воин 2'),
    new Warrior('Воин 3'),
);
$guildBlue = new Guild(
    'Blue',
    new Warrior('Воин 1'),
    new Archer('Лучник 1'),
    new Wizard('Волшебник 1'),
    new Wizard('Волшебник 2'),
);

Guild::viewInfo();

$units = [$guildRed->getMember(0), $guildBlue->getMember(1)];
while ($units[0]->state->status && $units[1]->state->status) {
    if ($units[0]->state->status) {
        if ($units[0] instanceof Wizard && $units[0]->state->hp < ($units[0]->state->maxHp / 2)) {
            $units[0]->heal($units[0]);
        } else {
            $units[0]->attack($units[1]);
        }
    }
    [$units[1], $units[0]] = $units;
}

Guild::viewInfo();