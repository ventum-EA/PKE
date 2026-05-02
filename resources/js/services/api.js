import axios from "axios";

/**
 * API client wrapping Axios with cookie auth (Laravel Sanctum SPA mode).
 *
 * Response interceptor normalizes the response shape so consumers
 * always get `{ data, meta, status }` regardless of the backend wrapper.
 *
 * Error interceptor:
 *   - On 401, dispatches a custom event that the router listens to
 *     and redirects to /login with a `redirect` query param. We do NOT
 *     use `window.location.href` (which forces a full page reload).
 *   - Other errors are passed through to the caller's catch block.
 */

const api = axios.create({
    baseURL: "/api",
    withCredentials: true,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

// Paths that should NOT trigger an auth-redirect when they 401
// (the user is already trying to authenticate)
const AUTH_PUBLIC_PATHS = ["/login", "/register", "/forgot-password", "/reset-password"];

api.interceptors.response.use(
    (response) => ({
        // Unwrap `{ payload: {...} }` envelope if present (from ApiResponse trait)
        data: response.data?.payload ?? response.data,
        meta: response.data?.payload?.meta ?? response.data?.meta ?? null,
        message: response.data?.message ?? null,
        status: response.status,
    }),
    (error) => {
        const status = error.response?.status;
        const responseData = error.response?.data ?? {};

        if (status === 401) {
            const currentPath = window.location.pathname;
            const isOnAuthPage = AUTH_PUBLIC_PATHS.some((p) => currentPath.startsWith(p));

            if (!isOnAuthPage) {
                // Notify the router via a custom event — keeps SPA navigation,
                // preserves scroll, and remembers where the user was going.
                window.dispatchEvent(
                    new CustomEvent("auth:unauthorized", {
                        detail: { redirect: currentPath + window.location.search },
                    })
                );
            }
        }

        // Always reject with a normalized error shape so callers can rely on:
        //   err.message, err.errors (field-level), err.status
        const rejection = {
            message: responseData.message || error.message || "Request failed",
            errors: responseData.errors || {},
            status: status ?? 0,
            payload: responseData.payload ?? null,
            response: error.response ?? null,
        };
        return Promise.reject(rejection);
    }
);

/**
 * Trigger a browser download of a server-generated file.
 * Bypasses the JSON response interceptor by using a raw axios call.
 */
async function downloadFile(url, fallbackName = "download") {
    const response = await axios.get(`/api${url}`, {
        withCredentials: true,
        responseType: "blob",
    });

    let filename = fallbackName;
    const disposition = response.headers["content-disposition"];
    if (disposition) {
        const match = disposition.match(/filename="?([^"]+)"?/);
        if (match) filename = match[1];
    }

    const blob = new Blob([response.data], {
        type: response.headers["content-type"] || "application/octet-stream",
    });
    const href = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = href;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(href);
}

export default {
    get: (url, params) => api.get(url, { params }),
    post: (url, data) => api.post(url, data),
    put: (url, data) => api.put(url, data),
    patch: (url, data) => api.patch(url, data),
    delete: (url, data) => api.delete(url, data ? { data } : undefined),
    download: downloadFile,
    async csrf() {
        await axios.get("/sanctum/csrf-cookie");
    },
};
