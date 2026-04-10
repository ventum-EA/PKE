// Vitest global setup — shared across all frontend tests.
// Runs once before the suite. Polyfills / globals / mocks live here.

import { vi } from "vitest";

// Mock window.matchMedia for components that check prefers-reduced-motion
if (typeof window !== "undefined") {
    Object.defineProperty(window, "matchMedia", {
        writable: true,
        value: (query) => ({
            matches: false,
            media: query,
            onchange: null,
            addListener: vi.fn(),
            removeListener: vi.fn(),
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }),
    });

    // Basic clipboard stub so copy-to-clipboard tests don't crash
    if (!navigator.clipboard) {
        Object.defineProperty(navigator, "clipboard", {
            value: { writeText: vi.fn().mockResolvedValue(undefined) },
            writable: true,
        });
    }

    // URL.createObjectURL stub for download tests
    if (!URL.createObjectURL) {
        URL.createObjectURL = vi.fn(() => "blob:mock");
        URL.revokeObjectURL = vi.fn();
    }
}
