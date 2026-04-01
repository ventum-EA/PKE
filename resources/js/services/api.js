import axios from "axios";

const api = axios.create({
    baseURL: "/api",
    withCredentials: true,
    headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

api.interceptors.response.use(
    (response) => ({
        data: response.data?.payload || response.data,
        meta: response.data?.payload?.meta || response.data?.meta || null,
        status: response.status,
    }),
    (error) => {
        if (error.response?.status === 401) {
            if (
                window.location.pathname !== "/login" &&
                window.location.pathname !== "/register"
            ) {
                window.location.href = "/login";
            }
        }
        return Promise.reject(error.response?.data || error);
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
    delete: (url, data) => api.delete(url, data ? { data } : undefined),
    download: downloadFile,
    async csrf() {
        await axios.get("/sanctum/csrf-cookie");
    },
};
