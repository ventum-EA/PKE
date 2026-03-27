import { ref } from "vue";

const show = ref(false);
const title = ref("");
const message = ref("");
const type = ref("danger");
let resolveFn = null;

export function useConfirm() {
    const confirm = (t, m, tp = "danger") =>
        new Promise((resolve) => {
            title.value = t;
            message.value = m;
            type.value = tp;
            show.value = true;
            resolveFn = resolve;
        });

    const onConfirm = () => { show.value = false; resolveFn?.(true); };
    const onCancel = () => { show.value = false; resolveFn?.(false); };

    return { show, title, message, type, confirm, onConfirm, onCancel };
}
