<?php

declare(strict_types=1);

namespace Gcego\Components\Contracts;

/**
 * Explicit opt-in for runtime-only property values such as callbacks.
 *
 * Runtime values are stored as-is and are never inspected or serialized by
 * this package, and making, validating, traversing or normalizing a
 * component never invokes them. Only evaluation runs one: the live engine and
 * Component::evaluateProperty() call an Evaluation\Computed closure.
 * Normalization replaces them with a RuntimePlaceholder that only carries the
 * implementing class name.
 */
interface RuntimeValue {}
