<?php
class Vector3 {
    public $x;
    public $y;
    public $z;

    public function __construct($x = 0.0, $y = 0.0, $z = 0.0) {
        $this->x = floatval($x);
        $this->y = floatval($y);
        $this->z = floatval($z);
    }

    public function __toString() {
        return "Vector3(x: $this->x, y: $this->y, z: $this->z)";
    }

    public function add(Vector3 $vector) {
        return new Vector3($this->x + $vector->x, $this->y + $vector->y, $this->z + $vector->z);
    }

    public function sub(Vector3 $vector) {
        return new Vector3($this->x - $vector->x, $this->y - $vector->y, $this->z - $vector->z);
    }

    public function mul(Vector3 $vector) {
        return new Vector3($this->x * $vector->x, $this->y * $vector->y, $this->z * $vector->z);
    }

    public function div(Vector3 $vector) {
        return new Vector3($this->x / $vector->x, $this->y / $vector->y, $this->z / $vector->z);
    }

    public function distance(Vector3 $vector) {
        return sqrt(pow($this->x - $vector->x, 2) + pow($this->y - $vector->y, 2) + pow($this->z - $vector->z, 2));
    }

    public function length() {
        return sqrt(pow($this->x, 2) + pow($this->y, 2) + pow($this->z, 2));
    }

    public function normalize() {
        $length = $this->length();
        return new Vector3($this->x / $length, $this->y / $length, $this->z / $length);
    }

    public function dot(Vector3 $vector) {
        return $this->x * $vector->x + $this->y * $vector->y + $this->z * $vector->z;
    }

    public function cross(Vector3 $vector) {
        return new Vector3(
            $this->y * $vector->z - $this->z * $vector->y,
            $this->z * $vector->x - $this->x * $vector->z,
            $this->x * $vector->y - $this->y * $vector->x
        );
    }

    public function angle(Vector3 $vector) {
        return acos($this->dot($vector) / ($this->length() * $vector->length()));
    }

    public function equals(Vector3 $vector) {
        return $this->x == $vector->x && $this->y == $vector->y && $this->z == $vector->z;
    }

    public function toArray() {
        return [$this->x, $this->y, $this->z];
    }

    public static function fromArray(array $array) {
        return new Vector3($array[0], $array[1], $array[2]);
    }

    public static function fromString(string $string) {
        $array = explode(',', $string);
        return new Vector3($array[0], $array[1], $array[2]);
    }

    public static function fromObject(object $object) {
        return new Vector3($object->x, $object->y, $object->z);
    }
}