/**
 * Safely gets a property from an object, ignoring properties on the prototype chain.
 * This prevents prototype pollution and unsafe property access when using user-provided keys.
 */
export function safeGet<V>(record: Record<string, V>, key: string, fallback: V): V {
    const descriptor = Object.getOwnPropertyDescriptor(record, key);
    return descriptor !== undefined ? (descriptor.value as V) : fallback;
}

/**
 * Safely sets a property on an object, rejecting dangerous keys like __proto__.
 * This prevents prototype pollution.
 */
export function safeSet<V>(record: Record<string, V>, key: string, value: V): void {
    if (key === '__proto__' || key === 'constructor' || key === 'prototype') {
        console.warn(`Refusing to set dangerous key: ${key}`);
        return;
    }
    Object.defineProperty(record, key, {
        value,
        writable: true,
        enumerable: true,
        configurable: true,
    });
}
