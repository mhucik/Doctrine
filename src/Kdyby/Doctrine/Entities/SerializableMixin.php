<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Entities;

use Kdyby;
use Kdyby\Doctrine\StaticClassException;
use ReflectionClass;
use Serializable;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
final class SerializableMixin
{

	/**
	 * @var array|\ReflectionClass[]
	 */
	private static $classes = [];

	/**
	 * @var array|\ReflectionProperty
	 */
	private static $properties = [];



	/**
	 * Static class - cannot be instantiated.
	 */
	final public function __construct()
	{
		throw new StaticClassException;
	}



	/**
	 * @param Serializable $object
	 *
	 * @return string
	 */
	public static function serialize(Serializable $object)
	{
		$data = [];

		$allowed = FALSE;
		if (method_exists($object, '__sleep')) {
			$allowed = (array)$object->__sleep();
		}

		$class = new ReflectionClass($object);

		do {
			/** @var \ReflectionProperty $propertyRefl */
			foreach ($class->getProperties() as $propertyRefl) {
				if ($allowed !== FALSE && !in_array($propertyRefl->getName(), $allowed)) {
					continue;

				} elseif ($propertyRefl->isStatic()) {
					continue;
				}

				// prefix private properties
				$prefix = $propertyRefl->isPrivate()
					? $propertyRefl->getDeclaringClass()->getName() . '::'
					: NULL;

				// save value
				$propertyRefl->setAccessible(TRUE);
				$data[$prefix . $propertyRefl->getName()] = $propertyRefl->getValue($object);
			}

		} while ($class = $class->getParentClass());

		return serialize($data);
	}



	/**
	 * @param Serializable $object
	 * @param string $serialized
	 */
	public static function unserialize(Serializable $object, $serialized)
	{
		$data = unserialize($serialized);

		foreach ($data as $target => $value) {
			if (strpos($target, '::') !== FALSE) {
				[$class, $name] = explode('::', $target, 2);
				$propertyRefl = self::getProperty($name, $class);

			} else {
				$propertyRefl = self::getProperty($target, $object);
			}

			$propertyRefl->setValue($object, $value);
		}

		if (method_exists($object, '__wakeup')) {
			$object->__wakeup();
		}
	}



	/**
	 * Class reflection cache.
	 *
	 * @param string $name
	 * @param string|object $class
	 *
	 * @return \ReflectionProperty
	 */
	private static function getProperty($name, $class)
	{
		$class = is_object($class) ? get_class($class) : $class;
		if (isset(self::$properties[$class][$name])) {
			return self::$properties[$class][$name];
		}

		if (!isset(self::$classes[$class])) {
			self::$classes[$class] = new ReflectionClass($class);
		}

		/** @var \ReflectionProperty $propRefl */
		$propRefl = self::$classes[$class]->getProperty($name);
		$propRefl->setAccessible(TRUE);

		if (!isset(self::$properties[$class])) {
			self::$properties[$class] = [];
		}
		return self::$properties[$class][$name] = $propRefl;
	}

}
