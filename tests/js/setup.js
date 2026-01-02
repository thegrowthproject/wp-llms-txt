/**
 * Jest setup file.
 *
 * Configures the test environment before each test file runs.
 */

import '@testing-library/jest-dom';
import { resetMocks } from './__mocks__/interactivity';

// Reset mocks before each test.
beforeEach( () => {
	resetMocks();
} );

// Mock navigator.clipboard.
Object.defineProperty( navigator, 'clipboard', {
	value: {
		writeText: jest.fn().mockResolvedValue( undefined ),
		readText: jest.fn().mockResolvedValue( '' ),
	},
	writable: true,
} );

// Mock fetch.
global.fetch = jest.fn( () =>
	Promise.resolve( {
		ok: true,
		text: () => Promise.resolve( '# Mock Markdown Content' ),
	} )
);

// Mock console.error to keep test output clean.
const originalError = console.error;
beforeAll( () => {
	console.error = jest.fn();
} );

afterAll( () => {
	console.error = originalError;
} );
