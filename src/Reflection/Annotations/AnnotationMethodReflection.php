<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

class AnnotationMethodReflection implements MethodReflection
{

	/** @var string */
	private $name;

	/** @var \PHPStan\Reflection\ClassReflection */
	private $declaringClass;

	/** @var  Type */
	private $returnType;

	/** @var bool */
	private $isStatic;

	/** @var \PHPStan\Reflection\Annotations\AnnotationsMethodParameterReflection[] */
	private $parameters;

	/** @var bool */
	private $isVariadic;

	/**
	 * @param string $name
	 * @param ClassReflection $declaringClass
	 * @param Type $returnType
	 * @param \PHPStan\Reflection\Annotations\AnnotationsMethodParameterReflection[] $parameters
	 * @param bool $isStatic
	 * @param bool $isVariadic
	 */
	public function __construct(
		string $name,
		ClassReflection $declaringClass,
		Type $returnType,
		array $parameters,
		bool $isStatic,
		bool $isVariadic
	)
	{
		$this->name = $name;
		$this->declaringClass = $declaringClass;
		$this->returnType = $returnType;
		$this->parameters = $parameters;
		$this->isStatic = $isStatic;
		$this->isVariadic = $isVariadic;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	public function isStatic(): bool
	{
		return $this->isStatic;
	}

	/**
	 * @return \PHPStan\Reflection\Annotations\AnnotationsMethodParameterReflection[]
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function isVariadic(): bool
	{
		return $this->isVariadic;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getReturnType(): Type
	{
		return $this->returnType;
	}

}
