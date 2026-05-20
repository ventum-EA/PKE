import { describe, it, expect, beforeEach } from "vitest";
import { createI18n } from "vue-i18n";
import { mount } from "@vue/test-utils";
import { defineComponent, h } from "vue";
import { useLocalized } from "@/composables/useLocalized";

function setupHarness(locale = "lv") {
    const i18n = createI18n({ legacy: false, locale, fallbackLocale: "en", messages: { lv: {}, en: {} } });
    let captured;
    const Harness = defineComponent({
        setup() { captured = useLocalized(); return () => h("div"); },
    });
    mount(Harness, { global: { plugins: [i18n] } });
    return captured;
}

describe("useLocalized", () => {
    describe("when locale is lv", () => {
        let localized;
        beforeEach(() => { localized = setupHarness("lv"); });

        it("prefers the _lv suffixed field", () => {
            const opening = { name: "Sicilian Defense", name_lv: "Sicīlijas aizstāvība" };
            expect(localized(opening, "name")).toBe("Sicīlijas aizstāvība");
        });

        it("falls back to bare field when _lv is missing", () => {
            expect(localized({ title: "Pin tactics" }, "title")).toBe("Pin tactics");
        });

        it("falls back to bare field when _lv is empty", () => {
            expect(localized({ title: "Pin tactics", title_lv: "" }, "title")).toBe("Pin tactics");
        });

        it("returns empty string for missing fields", () => {
            expect(localized({}, "missing")).toBe("");
            expect(localized(null, "missing")).toBe("");
            expect(localized(undefined, "missing")).toBe("");
        });
    });

    describe("when locale is en", () => {
        let localized;
        beforeEach(() => { localized = setupHarness("en"); });

        it("uses name_en when available", () => {
            const o = { name: "default", name_en: "English Name", name_lv: "Latvian" };
            expect(localized(o, "name")).toBe("English Name");
        });

        it("falls back to _lv when _en is absent (content is authored in LV)", () => {
            // This is by design — Latvian text is better than nothing
            const o = { name: "Sicilian Defense", name_lv: "Sicīlijas aizstāvība" };
            expect(localized(o, "name")).toBe("Sicīlijas aizstāvība");
        });

        it("uses bare field when no suffixed fields exist", () => {
            expect(localized({ title: "Plain" }, "title")).toBe("Plain");
        });
    });
});
