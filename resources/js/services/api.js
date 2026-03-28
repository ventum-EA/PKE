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
            if (window.location.pathname !== "/login" && window.location.pathname !== "/register") {
                window.location.href = "/login";
            }
        }
        return Promise.reject(error.response?.data || error);
    }
);

export default {
    get: (url, params) => api.get(url, { params }),
    post: (url, data) => api.post(url, data),
    put: (url, data) => api.put(url, data),
    delete: (url) => api.delete(url),
    async csrf() {
        await axios.get("/sanctum/csrf-cookie");
    },
};
