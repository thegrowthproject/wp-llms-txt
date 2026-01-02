/**
 * Mock for @wordpress/interactivity.
 *
 * Provides test doubles for the Interactivity API functions.
 */

// Store for mock context data.
let mockContext = {};
let mockState = {};
let registeredStores = {};

/**
 * Mock getContext function.
 *
 * @return {Object} The mock context.
 */
export const getContext = jest.fn( () => mockContext );

/**
 * Mock store function.
 *
 * @param {string} namespace  Store namespace.
 * @param {Object} definition Store definition.
 * @return {Object} The store object.
 */
export const store = jest.fn( ( namespace, definition ) => {
	// Create state object that preserves getters.
	const stateObj = {};

	// First add mockState properties.
	Object.keys( mockState ).forEach( ( key ) => {
		Object.defineProperty( stateObj, key, {
			value: mockState[ key ],
			enumerable: true,
			writable: true,
		} );
	} );

	// Then add definition.state properties (preserving getters).
	if ( definition.state ) {
		Object.keys( definition.state ).forEach( ( key ) => {
			const descriptor = Object.getOwnPropertyDescriptor( definition.state, key );
			if ( descriptor ) {
				Object.defineProperty( stateObj, key, {
					...descriptor,
					enumerable: true,
				} );
			}
		} );
	}

	const storeObj = {
		state: stateObj,
		actions: definition.actions || {},
		callbacks: definition.callbacks || {},
	};

	registeredStores[ namespace ] = storeObj;

	return storeObj;
} );

/**
 * Set mock context for testing.
 *
 * @param {Object} context The context to set.
 */
export const setMockContext = ( context ) => {
	mockContext = context;
};

/**
 * Set mock state for testing.
 *
 * @param {Object} state The state to set.
 */
export const setMockState = ( state ) => {
	mockState = state;
};

/**
 * Get a registered store by namespace.
 *
 * @param {string} namespace Store namespace.
 * @return {Object|undefined} The store or undefined.
 */
export const getStore = ( namespace ) => {
	return registeredStores[ namespace ];
};

/**
 * Reset all mocks and stores.
 */
export const resetMocks = () => {
	mockContext = {};
	mockState = {};
	registeredStores = {};
	getContext.mockClear();
	store.mockClear();
};
