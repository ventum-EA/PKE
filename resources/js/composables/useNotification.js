import { ref } from "vue";

const show = ref(false);
const message = ref("");
const type = ref("success");

let timer = null;

export function useNotification() {
    const notify = (msg, t = "success", duration = 3000) => {
        clearTimeout(timer);
        message.value = msg;
        type.value = t;
        show.value = true;
        timer = setTimeout(() => (show.value = false), duration);
    };
    return { show, message, type, notify };
}
