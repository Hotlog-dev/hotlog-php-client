<?php


class Hotlog
{
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';

    private static function parse($object, &$output = [], $depth = 0)
    {
        $depth++;
        $orc = new ReflectionClass($object);
        $output['_hotlog'] = [
            '_type'    => $orc->getName(),
            '_preview' => $orc->getShortName()
        ];
        if ($depth > 5)
        {
            return [
                '_hotlog' => $output['_deburger'],
                '#hotlog' => 'Max depth reached.',
            ];
        }
        foreach ($orc->getProperties(ReflectionProperty::IS_PRIVATE) as $property)
        {
            self::parseProperty($output, $depth, $object, $property, '-');
        }
        foreach ($orc->getProperties(ReflectionProperty::IS_PROTECTED) as $property)
        {
            self::parseProperty($output, $depth, $object, $property, '#');
        }
        foreach ($orc->getProperties(ReflectionProperty::IS_PUBLIC) as $property)
        {
            self::parseProperty($output,$depth, $object, $property, '+');
        }

        return $output;
    }

    private static function parseProperty(&$output, $depth, $object, $property, $prefix = '')
    {
        $property->setAccessible(true);
        $value = $property->getValue($object);
        $type = is_object($value)
            ? self::TYPE_OBJECT
            : gettype($value);
        switch ($type)
        {
            case self::TYPE_OBJECT:
                $_output = [];
                $output[$prefix . $property->getName()] = self::parse($property->getValue($object),$_output , $depth);
                break;
            default:
                $output[$prefix . $property->getName()] = $property->getValue($object);
                break;
        }
    }

    private static function walk($var, $depth = 0)
    {
        $output = [];
        if (is_object($var))
        {
            return self::parse($var, $output, $depth);
        }
        if (is_array($var))
        {
            foreach ($var as $key => $value)
            {
                $output['_hotlog'] = [
                    '_type'    => 'Array',
                    '_preview' => 'Array'
                ];
                $output[$key] = self::walk($value);
            }

            return $output;
        }

        return $var;
    }

    /**
     * @param $var
     *
     * @throws Throwable
     */
    public static function dump($var)
    {
        $ch = curl_init();
        $parameters = [
            'url'  => $_ENV['HOTLOG_URL'],
        ];
        try
        {
            // set url
            curl_setopt($ch, CURLOPT_URL, "{$parameters['url']}/api/logs");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'date'  => time(),
                'log'    => [self::walk($var)],
                'client' => [
                    'id' => $_ENV['HOTLOG_CLIENT_ID'],
                    'name' => $_ENV['HOTLOG_CLIENT_NAME'],
                    'color' => $_ENV['HOTLOG_CLIENT_COLOR']
                ]
            ]));

            $output = curl_exec($ch);
        }
        catch (\Throwable $th)
        {
            throw $th;
        }
        finally
        {
            curl_close($ch);
        }
    }
}


function hotlog($var)
{
    Hotlog::dump($var);
}
