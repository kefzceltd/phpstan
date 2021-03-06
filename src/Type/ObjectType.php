<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\UniversalObjectCratesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;

class ObjectType implements TypeWithClassName
{

	use TruthyBooleanTypeTrait;

	/** @var string */
	private $className;

	public function __construct(string $className)
	{
		$this->className = $className;
	}

	public function getClassName(): string
	{
		return $this->className;
	}

	public function hasProperty(string $propertyName): bool
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->className)) {
			return false;
		}

		return $broker->getClass($this->className)->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, Scope $scope): PropertyReflection
	{
		$broker = Broker::getInstance();
		return $broker->getClass($this->className)->getProperty($propertyName, $scope);
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [$this->className];
	}

	public function accepts(Type $type): bool
	{
		if ($type instanceof StaticType) {
			return $this->checkSubclassAcceptability($type->getBaseClass());
		}

		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this);
		}

		if (!$type instanceof TypeWithClassName) {
			return false;
		}

		return $this->checkSubclassAcceptability($type->getClassName());
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof ObjectWithoutClassType) {
			return TrinaryLogic::createMaybe();
		}

		if (!$type instanceof TypeWithClassName) {
			return TrinaryLogic::createNo();
		}

		$thisClassName = $this->className;
		$thatClassName = $type->getClassName();

		if ($thatClassName === $thisClassName) {
			return TrinaryLogic::createYes();
		}

		$broker = Broker::getInstance();

		if (!$broker->hasClass($thisClassName) || !$broker->hasClass($thatClassName)) {
			return TrinaryLogic::createMaybe();
		}

		$thisClassReflection = $broker->getClass($thisClassName);
		$thatClassReflection = $broker->getClass($thatClassName);

		if ($thisClassReflection->getName() === $thatClassReflection->getName()) {
			return TrinaryLogic::createYes();
		}

		if ($thatClassReflection->isSubclassOf($thisClassName)) {
			return TrinaryLogic::createYes();
		}

		if ($thisClassReflection->isSubclassOf($thatClassName)) {
			return TrinaryLogic::createMaybe();
		}

		if ($thisClassReflection->isInterface() && !$thatClassReflection->getNativeReflection()->isFinal()) {
			return TrinaryLogic::createMaybe();
		}

		if ($thatClassReflection->isInterface() && !$thisClassReflection->getNativeReflection()->isFinal()) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

	private function checkSubclassAcceptability(string $thatClass): bool
	{
		if ($this->className === $thatClass) {
			return true;
		}

		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->className) || !$broker->hasClass($thatClass)) {
			return false;
		}

		$thisReflection = $broker->getClass($this->className);
		$thatReflection = $broker->getClass($thatClass);

		if ($thisReflection->getName() === $thatReflection->getName()) {
			// class alias
			return true;
		}

		if ($thisReflection->isInterface() && $thatReflection->isInterface()) {
			return $thatReflection->getNativeReflection()->implementsInterface($this->className);
		}

		return $thatReflection->isSubclassOf($this->className);
	}

	public function describe(VerbosityLevel $level): string
	{
		return $this->className;
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ErrorType();
	}

	public function toFloat(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->className)) {
			return new ErrorType();
		}

		$classReflection = $broker->getClass($this->className);
		if ($classReflection->hasMethod('__toString')) {
			return new StringType();
		}

		return new ErrorType();
	}

	public function toArray(): Type
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->className)) {
			return new ArrayType(new MixedType(), new MixedType());
		}

		$classReflection = $broker->getClass($this->className);
		if (UniversalObjectCratesClassReflectionExtension::isUniversalObjectCrate(
			$broker,
			$broker->getUniversalObjectCratesClasses(),
			$classReflection
		)) {
			return new ArrayType(new MixedType(), new MixedType());
		}
		$arrayKeys = [];
		$arrayValues = [];

		do {
			foreach ($classReflection->getNativeReflection()->getProperties() as $nativeProperty) {
				if ($nativeProperty->isStatic()) {
					continue;
				}

				$declaringClass = $broker->getClass($nativeProperty->getDeclaringClass()->getName());
				$property = $declaringClass->getNativeProperty($nativeProperty->getName());
				$arrayKeys[] = new ConstantStringType(sprintf(
					'%s%s',
					$declaringClass->getName(),
					$nativeProperty->getName()
				));
				$arrayValues[] = $property->getType();
			}

			$classReflection = $classReflection->getParentClass();
		} while ($classReflection !== false);

		return new ConstantArrayType($arrayKeys, $arrayValues);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function canCallMethods(): TrinaryLogic
	{
		if (strtolower($this->className) === 'stdclass') {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createYes();
	}

	public function hasMethod(string $methodName): bool
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->className)) {
			return false;
		}

		return $broker->getClass($this->className)->hasMethod($methodName);
	}

	public function getMethod(string $methodName, Scope $scope): MethodReflection
	{
		$broker = Broker::getInstance();
		return $broker->getClass($this->className)->getMethod($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasConstant(string $constantName): bool
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->className)) {
			return false;
		}

		return $broker->getClass($this->className)->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ClassConstantReflection
	{
		$broker = Broker::getInstance();
		return $broker->getClass($this->className)->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->isInstanceOf(\Traversable::class);
	}

	public function getIterableKeyType(): Type
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->className)) {
			return new ErrorType();
		}

		$classReflection = $broker->getClass($this->className);

		if ($classReflection->isSubclassOf(\Iterator::class) && $classReflection->hasNativeMethod('key')) {
			return $classReflection->getNativeMethod('key')->getReturnType();
		}

		if ($classReflection->isSubclassOf(\IteratorAggregate::class) && $classReflection->hasNativeMethod('getIterator')) {
			return RecursionGuard::run($this, function () use ($classReflection) {
				return $classReflection->getNativeMethod('getIterator')->getReturnType()->getIterableKeyType();
			});
		}

		if ($classReflection->isSubclassOf(\Traversable::class)) {
			return new MixedType();
		}

		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->className)) {
			return new ErrorType();
		}

		$classReflection = $broker->getClass($this->className);

		if ($classReflection->isSubclassOf(\Iterator::class) && $classReflection->hasNativeMethod('current')) {
			return $classReflection->getNativeMethod('current')->getReturnType();
		}

		if ($classReflection->isSubclassOf(\IteratorAggregate::class) && $classReflection->hasNativeMethod('getIterator')) {
			return RecursionGuard::run($this, function () use ($classReflection) {
				return $classReflection->getNativeMethod('getIterator')->getReturnType()->getIterableValueType();
			});
		}

		if ($classReflection->isSubclassOf(\Traversable::class)) {
			return new MixedType();
		}

		return new ErrorType();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->isInstanceOf(\ArrayAccess::class);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->className)) {
			return new ErrorType();
		}

		$classReflection = $broker->getClass($this->className);

		if ($classReflection->isSubclassOf(\ArrayAccess::class)) {
			if ($classReflection->hasNativeMethod('offsetGet')) {
				return RecursionGuard::run($this, function () use ($classReflection) {
					return $classReflection->getNativeMethod('offsetGet')->getReturnType();
				});
			}

			return new MixedType();
		}

		return new ErrorType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		// in the future we may return intersection of $this and OffsetAccessibleType()
		return $this;
	}

	public function isCallable(): TrinaryLogic
	{
		$parametersAcceptor = $this->findCallableParametersAcceptor();
		if ($parametersAcceptor === null) {
			return TrinaryLogic::createNo();
		}

		if ($parametersAcceptor instanceof TrivialParametersAcceptor) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createYes();
	}

	public function getCallableParametersAcceptor(Scope $scope): ParametersAcceptor
	{
		if ($this->className === \Closure::class) {
			return new TrivialParametersAcceptor();
		}
		$parametersAcceptor = $this->findCallableParametersAcceptor();
		if ($parametersAcceptor === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $parametersAcceptor;
	}

	private function findCallableParametersAcceptor(): ?ParametersAcceptor
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->className)) {
			return new TrivialParametersAcceptor();
		}

		$classReflection = $broker->getClass($this->className);
		if ($classReflection->hasNativeMethod('__invoke')) {
			return $classReflection->getNativeMethod('__invoke');
		}

		if (!$classReflection->getNativeReflection()->isFinal()) {
			return new TrivialParametersAcceptor();
		}

		return null;
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['className']);
	}

	public function isInstanceOf(string $className): TrinaryLogic
	{
		$broker = Broker::getInstance();

		if (!$broker->hasClass($this->className)) {
			return TrinaryLogic::createMaybe();
		}

		$classReflection = $broker->getClass($this->className);
		if ($classReflection->isSubclassOf($className) || $classReflection->getName() === $className) {
			return TrinaryLogic::createYes();
		}

		if ($classReflection->isInterface()) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

}
