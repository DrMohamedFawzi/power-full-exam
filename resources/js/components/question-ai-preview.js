/**
 * Review step for AI-generated questions: the teacher edits/removes drafts
 * client-side, then this posts the kept set to AiExamController::store.
 */
export default ({ storeUrl, redirectUrl, questions }) => ({
    questions,
    saving: false,
    error: null,

    remove(index) {
        this.questions.splice(index, 1);
    },

    async submit() {
        if (this.questions.length === 0) {
            this.error = 'لم يتبقَّ أي سؤال لحفظه.';
            return;
        }

        this.saving = true;
        this.error = null;

        try {
            await window.aegis.request(this.storeUrl, { method: 'POST', body: { questions: this.questions } });
            window.location.href = this.redirectUrl;
        } catch (e) {
            this.error = e.payload?.message ?? 'تعذّر حفظ الأسئلة.';
            this.saving = false;
        }
    },
});
