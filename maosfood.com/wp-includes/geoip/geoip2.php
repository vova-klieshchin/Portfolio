<?php

define('_MM_MAX_INT_BYTES', (log(PHP_INT_MAX, 2) - 1) / 8);

class mReader
{
    private static $DATA_SECTION_SEPARATOR_SIZE = 16;
    private static $METADATA_START_MARKER = "\xAB\xCD\xEFMaxMind.com";
    private static $METADATA_START_MARKER_LENGTH = 14;
    private static $METADATA_MAX_SIZE = 131072;

    private $decoder;
    private $fileHandle;
    private $fileSize;
    private $ipV4Start;
    private $metadata;

    public function __construct($d)
    {
        if(func_num_args() !== 1) trigger_error('The constructor takes exactly one argument.');
        if(!is_readable($d)) trigger_error("The file \"$d\" does not exist or is not readable.");
        $this->fileHandle = fopen($d, 'rb'); if($this->fileHandle === false) trigger_error("Error opening $d.");
        $this->fileSize = filesize($d); if($this->fileSize === false) trigger_error("Error determining the size of $d.");
        $s = $this->findMetadataStart($d); $d = new Decoder($this->fileHandle, $s); list($a) = $d->decode($s);
        $this->metadata = new Metadata($a);
        $this->decoder = new Decoder($this->fileHandle, $this->metadata->searchTreeSize + self::$DATA_SECTION_SEPARATOR_SIZE);
        $this->ipV4Start = $this->ipV4StartNode();
    }

    public function get($i)
    {
        if(func_num_args() !== 1) trigger_error('Method takes exactly one argument.');
        list($r) = $this->getWithPrefixLen($i); return $r;
    }

    public function getWithPrefixLen($i)
    {
        if(func_num_args() !== 1) trigger_error('Method takes exactly one argument.');
        if(!is_resource($this->fileHandle)) trigger_error('Attempt to read from a closed MaxMind DB.');
        if(!filter_var($i, FILTER_VALIDATE_IP)) trigger_error("The value $ipAddress is not a valid IP address.");
        list($p, $n) = $this->findAddressInTree($i);
        if ($p === 0) return [null, $n];
        return [$this->resolveDataPointer($p), $n];
    }

    private function findAddressInTree($e)
    {
        $r = unpack('C*', inet_pton($e)); $b = count($r) * 8; $o = 0; $m = $this->metadata;
        if ($m->ipVersion === 6) { if ($b === 32) $o = $this->ipV4Start; } elseif ($m->ipVersion === 4 && $b === 128) trigger_error("Error looking up $e. You attempted to look up an IPv6 address in an IPv4-only database.");
        $c = $m->nodeCount;
        for($i = 0; $i < $b && $o < $c; ++$i) { $t = 0xFF & $r[($i >> 3) + 1]; $h = 1 & ($t >> 7 - ($i % 8)); $o = $this->readNode($o, $h); }
        if($o === $c) return [0, $i]; elseif ($o > $c) return [$o, $i];
        trigger_error('Something bad happened');
    }

    private function ipV4StartNode()
    {
        if($this->metadata->ipVersion === 4) return 0; $n = 0;
        for ($i = 0; $i < 96 && $n < $this->metadata->nodeCount; ++$i) $n = $this->readNode($n, 0);
        return $n;
    }

    private function readNode($e, $i)
    {
        $o = $e * $this->metadata->nodeByteSize;
        switch ($this->metadata->recordSize) {
            case 24:  $b = Util::read($this->fileHandle, $o + $i * 3, 3); list(, $n) = unpack('N', "\x00" . $b); return $n;
            case 28:  $b = Util::read($this->fileHandle, $o + 3 * $i, 4); if($i === 0) $m = (0xF0 & ord($b[3])) >> 4; else $m = 0x0F & ord($b[0]); list(, $n) = unpack('N', chr($m) . substr($b, $i, 3)); return $n;
            case 32: $b = Util::read($this->fileHandle, $o + $i * 4, 4); list(, $n) = unpack('N', $b); return $n;
            default: trigger_error('Unknown record size: '.$this->metadata->recordSize);
        }
    }

    private function resolveDataPointer($p)
    {
        $r = $p - $this->metadata->nodeCount + $this->metadata->searchTreeSize;
        if($r > $this->fileSize) trigger_error("The MaxMind DB file's search tree is corrupt");
        list($d) = $this->decoder->decode($r); return $d;
    }

    private function findMetadataStart($f)
    {
        $n = $this->fileHandle; $t = fstat($n); $s = $t['size']; $m = self::$METADATA_START_MARKER; $l = self::$METADATA_START_MARKER_LENGTH; $d = min(self::$METADATA_MAX_SIZE, $s) - $l;
        for($i = 0; $i <= $d; ++$i) { for($j = 0; $j < $l; ++$j) { fseek($n, $s - $i - $j - 1); $b = fgetc($n); if($b !== $m[$l - $j - 1]) continue 2; } return $s - $i; }
        trigger_error("Error opening database file ($f). Is this a valid MaxMind DB file?");
    }

    public function metadata()
    {
        if(func_num_args()) trigger_error('Method takes no arguments.');
        if(!is_resource($this->fileHandle)) trigger_error('Attempt to read from a closed MaxMind DB.');
        return $this->metadata;
    }

    public function close()
    {
        if(!is_resource($this->fileHandle)) trigger_error('Attempt to close a closed MaxMind DB.');
        fclose($this->fileHandle);
    }
}

class Util
{
    public static function read($s, $o, $n)
    {
        if($n === 0) return '';
        if(fseek($s, $o) === 0) { $v = fread($s, $n); if(ftell($s) - $o === $n) return $v; }
        trigger_error('The MaxMind DB file contains bad data');
    }
}

class Metadata
{
    private $binaryFormatMajorVersion;
    private $binaryFormatMinorVersion;
    private $buildEpoch;
    private $databaseType;
    private $description;
    private $ipVersion;
    private $languages;
    private $nodeByteSize;
    private $nodeCount;
    private $recordSize;
    private $searchTreeSize;

    public function __construct($m)
    {
        $this->binaryFormatMajorVersion = $m['binary_format_major_version'];
        $this->binaryFormatMinorVersion = $m['binary_format_minor_version'];
        $this->buildEpoch = $m['build_epoch'];
        $this->databaseType = $m['database_type'];
        $this->languages = $m['languages'];
        $this->description = $m['description'];
        $this->ipVersion = $m['ip_version'];
        $this->nodeCount = $m['node_count'];
        $this->recordSize = $m['record_size'];
        $this->nodeByteSize = $this->recordSize / 4;
        $this->searchTreeSize = $this->nodeCount * $this->nodeByteSize;
    }

    public function __get($var)
    {
        return $this->$var;
    }
}

class Decoder
{
    private $fileStream;
    private $pointerBase;
    private $pointerBaseByteSize;
    
    private $pointerTestHack;
    private $switchByteOrder;

    const _EXTENDED = 0;
    const _POINTER = 1;
    const _UTF8_STRING = 2;
    const _DOUBLE = 3;
    const _BYTES = 4;
    const _UINT16 = 5;
    const _UINT32 = 6;
    const _MAP = 7;
    const _INT32 = 8;
    const _UINT64 = 9;
    const _UINT128 = 10;
    const _ARRAY = 11;
    const _CONTAINER = 12;
    const _END_MARKER = 13;
    const _BOOLEAN = 14;
    const _FLOAT = 15;

    public function __construct($f, $p = 0, $h = false)
	{
        $this->fileStream = $f;
        $this->pointerBase = $p;
        $this->pointerBaseByteSize = $p > 0 ? log($p, 2) / 8 : 0;
        $this->pointerTestHack = $h;
        $this->switchByteOrder = $this->isPlatformLittleEndian();
    }

    public function decode($o)
    {
        list(, $c) = unpack('C', Util::read($this->fileStream, $o, 1)); ++$o; $t = $c >> 5;
        if($t === self::_POINTER) { list($p, $o) = $this->decodePointer($c, $o); if($this->pointerTestHack) return [$p]; list($r) = $this->decode($p); return [$r, $o]; }
        if($t === self::_EXTENDED) { list(, $y) = unpack('C', Util::read($this->fileStream, $o, 1)); $t = $y + 7; if($t < 8) trigger_error('Something went horribly wrong in the decoder. An extended type resolved to a type number < 8 ('.$t.')'); ++$o; }
        list($s, $o) = $this->sizeFromCtrlByte($c, $o); return $this->decodeByType($t, $o, $s);
    }

    private function decodeByType($t, $o, $s)
    {
        switch ($t) {
            case self::_MAP:      return $this->decodeMap($s, $o);
            case self::_ARRAY:    return $this->decodeArray($s, $o);
            case self::_BOOLEAN:  return [$this->decodeBoolean($s), $o];
        }
        $n = $o + $s; $b = Util::read($this->fileStream, $o, $s);
        switch ($t) {
            case self::_BYTES:
            case self::_UTF8_STRING: return [$b, $n];
            case self::_DOUBLE:      $this->verifySize(8, $s); return [$this->decodeDouble($b), $n];
            case self::_FLOAT:       $this->verifySize(4, $s); return [$this->decodeFloat($b), $n];
            case self::_INT32:       return [$this->decodeInt32($b, $s), $n];
            case self::_UINT16:
            case self::_UINT32:
            case self::_UINT64:
            case self::_UINT128:     return [$this->decodeUint($b, $s), $n];
            default:                 trigger_error('Unknown or unexpected type: '.$t);
        }
    }

    private function verifySize($e, $a)
    {
        if($e !== $a) trigger_error("The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)");
    }

    private function decodeArray($s, $o)
    {
        $a = [];
        for($i = 0; $i < $s; ++$i) { list($v, $o) = $this->decode($o); array_push($a, $v); }
        return [$a, $o];
    }

    private function decodeBoolean($s)
    {
        return $s === 0 ? false : true;
    }

    private function decodeDouble($b)
    {
        list(, $d) = unpack('d', $this->maybeSwitchByteOrder($b)); return $d;
    }

    private function decodeFloat($b)
    {
        list(, $f) = unpack('f', $this->maybeSwitchByteOrder($b)); return $f;
    }

    private function decodeInt32($b, $s)
    {
        switch ($s) {
            case 0: return 0;
            case 1:
            case 2:
            case 3: $b = str_pad($b, 4, "\x00", STR_PAD_LEFT); break;
            case 4: break;
            default: trigger_error("The MaxMind DB file's data section contains bad data (unknown data type or corrupt data)");
        }
        list(, $i) = unpack('l', $this->maybeSwitchByteOrder($b));
        return $i;
    }

    private function decodeMap($s, $offset)
    {
        $m = [];
        for($i = 0; $i < $s; ++$i) { list($k, $offset) = $this->decode($offset); list($v, $offset) = $this->decode($offset); $m[$k] = $v; }
        return [$m, $offset];
    }

    private function decodePointer($c, $o)
    {
        $p = (($c >> 3) & 0x3) + 1; $b = Util::read($this->fileStream, $o, $p); $o = $o + $p;
        switch ($p) {
            case 1: $k = (pack('C', $c & 0x7)).$b; list(, $n) = unpack('n', $k); $n += $this->pointerBase; break;
            case 2: $k = "\x00".(pack('C', $c & 0x7)).$b; list(, $n) = unpack('N', $k); $n += $this->pointerBase + 2048; break;
            case 3: $k = (pack('C', $c & 0x7)).$b; list(, $n) = unpack('N', $k); $n += $this->pointerBase + 526336; break;
            case 4: $m = $this->decodeUint($b, $p); $l = $p + $this->pointerBaseByteSize; if($l <= _MM_MAX_INT_BYTES) $n = $m + $this->pointerBase; elseif (extension_loaded('gmp')) $n = gmp_strval(gmp_add($m, $this->pointerBase)); elseif (extension_loaded('bcmath')) $n = bcadd($m, $this->pointerBase); else trigger_error('The gmp or bcmath extension must be installed to read this database.');
        }
        return [$n, $o];
    }

    private function decodeUint($y, $l)
    {
        if($l === 0) return 0; $n = 0;
        for($i = 0; $i < $l; ++$i) {
            $p = ord($y[$i]);
            if ($l <= _MM_MAX_INT_BYTES) $n = ($n << 8) + $p;
            elseif (extension_loaded('gmp')) $n = gmp_strval(gmp_add(gmp_mul($n, 256), $p));
            elseif (extension_loaded('bcmath')) $n = bcadd(bcmul($n, 256), $p);
            else trigger_error('The gmp or bcmath extension must be installed to read this database.');
        }
        return $n;
    }

    private function sizeFromCtrlByte($c, $o)
    {
        $s = $c & 0x1f;
        if($s < 29) return [$s, $o];
        $r = $s - 28; $y = Util::read($this->fileStream, $o, $r);
        if($s === 29) $s = 29 + ord($y); elseif ($s === 30) { list(, $a) = unpack('n', $y); $s = 285 + $a; } elseif ($s > 30) { list(, $a) = unpack('N', "\x00" . $y); $s = ($a & (0x0FFFFFFF >> (32 - (8 * $r)))) + 65821; }
        return [$s, $o + $r];
    }

    private function maybeSwitchByteOrder($bytes)
    {
        return $this->switchByteOrder ? strrev($bytes) : $bytes;
    }

    private function isPlatformLittleEndian()
    {
        $t = 0x00FF; $p = pack('S', $t); return $t === current(unpack('v', $p));
    }
}

class Reader implements ProviderInterface
{
    private $dbReader;
    private $locales;

    public function __construct($filename, $locales = ['en']) {
        $this->dbReader = new mReader($filename);
        $this->locales = $locales;
    }

    public function city($ipAddress)
    {
        return $this->modelFor('City', 'City', $ipAddress);
    }

    public function country($ipAddress)
    {
        return $this->modelFor('Country', 'Country', $ipAddress);
    }

    public function asn($ipAddress)
    {
        return $this->flatModelFor('Asn', 'GeoLite2-ASN', $ipAddress);
    }

    private function modelFor($class, $type, $ipAddress)
    {
        $record = $this->getRecord($class, $type, $ipAddress);
        $record['traits']['ip_address'] = $ipAddress;
        return new $class($record, $this->locales);
    }

    private function flatModelFor($class, $type, $ipAddress)
    {
        $record = $this->getRecord($class, $type, $ipAddress);
        $record['ip_address'] = $ipAddress;
        return new $class($record);
    }

    private function getRecord($class, $type, $ipAddress)
    {
        if (strpos($this->metadata()->databaseType, $type) === false) {
            $method = lcfirst($class);
            trigger_error("The $method method cannot be used to open a ".$this->metadata()->databaseType.' database');
        }
        $record = $this->dbReader->get($ipAddress);
        if($record === null) trigger_error("The address $ipAddress is not in the database.");
        if(!is_array($record)) trigger_error("Expected an array when looking up $ipAddress but received: ".gettype($record));
        return $record;
    }

    public function metadata()
    {
        return $this->dbReader->metadata();
    }

    public function close()
    {
        $this->dbReader->close();
    }
}

interface ProviderInterface
{
    public function country($ipAddress);
    public function city($ipAddress);
}

abstract class AbstractRecord
{
    private $record;

    public function __construct($record)
    {
        $this->record = isset($record) ? $record : [];
    }

    public function __get($attr)
    {
        $key = $this->attributeToKey($attr);
        if($this->__isset($attr)) return $this->record[$key];
        elseif($this->validAttribute($attr)) {
            if(preg_match('/^is_/', $key)) return false;
            return null;
        }
        trigger_error("Unknown attribute: $attr");
    }

    public function __isset($attr)
    {
        return $this->validAttribute($attr) && isset($this->record[$this->attributeToKey($attr)]);
    }

    private function attributeToKey($attr)
    {
        return strtolower(preg_replace('/([A-Z])/', '_\1', $attr));
    }

    private function validAttribute($attr)
    {
        return in_array($attr, $this->validAttributes, true);
    }

    public function jsonSerialize()
    {
        return $this->record;
    }
}

abstract class AbstractModel
{
    protected $raw;

    public function __construct($raw)
    {
        $this->raw = $raw;
    }

    protected function get($field)
    {
        if(isset($this->raw[$field])) return $this->raw[$field];
        if(preg_match('/^is_/', $field)) return false;
        return null;
    }

    public function __get($attr)
    {
        if($attr !== 'instance' && property_exists($this, $attr)) return $this->$attr;
        trigger_error("Unknown attribute: $attr");
    }

    public function __isset($attr)
    {
        return $attr !== 'instance' && isset($this->$attr);
    }

    public function jsonSerialize()
    {
        return $this->raw;
    }
}

class Asn extends AbstractModel
{
    protected $autonomousSystemNumber;
    protected $autonomousSystemOrganization;
    protected $ipAddress;

    public function __construct($raw)
    {
        parent::__construct($raw);
        $this->autonomousSystemNumber = $this->get('autonomous_system_number');
        $this->autonomousSystemOrganization = $this->get('autonomous_system_organization');
        $this->ipAddress = $this->get('ip_address');
    }
}

class Country extends AbstractModel
{
    protected $continent;
    protected $country;

    public function __construct($raw, $locales = ['en'])
    {
        parent::__construct($raw);
        $this->continent = new rContinent($this->get('continent'), $locales);
        $this->country = new rCountry($this->get('country'), $locales);
    }
}

class City extends Country
{
    protected $city;
    protected $location;
    protected $postal;
    
    public function __construct($raw, $locales = ['en'])
    {
        parent::__construct($raw, $locales);

        $this->city = new rCity($this->get('city'), $locales);
        $this->location = new rLocation($this->get('location'));
        $this->postal = new rPostal($this->get('postal'));

    }
}

abstract class AbstractPlaceRecord extends AbstractRecord
{
    private $locales;

    public function __construct($record, $locales = ['en'])
    {
        $this->locales = $locales;
        parent::__construct($record);
    }

    public function __get($attr)
    {
        if($attr === 'name') return $this->name();
        return parent::__get($attr);
    }

    public function __isset($attr)
    {
        if($attr === 'name') return $this->firstSetNameLocale() === null ? false : true;
        return parent::__isset($attr);
    }

    private function name()
    {
        $locale = $this->firstSetNameLocale();
        return $locale === null ? null : $this->names[$locale];
    }

    private function firstSetNameLocale()
    {
        foreach ($this->locales as $locale) if(isset($this->names[$locale])) return $locale;
        return null;
    }
}

class rCity extends AbstractPlaceRecord
{
    protected $validAttributes = ['confidence', 'geonameId', 'names'];
}

class rContinent extends AbstractPlaceRecord
{
    protected $validAttributes = ['code', 'geonameId', 'names'];
}

class rCountry extends AbstractPlaceRecord
{
    protected $validAttributes = ['geonameId', 'isoCode', 'names'];
}

class rLocation extends AbstractRecord
{
    protected $validAttributes = ['latitude', 'longitude', 'postalCode', 'timeZone'];
}

class rPostal extends AbstractRecord
{
    protected $validAttributes = ['code'];
}

