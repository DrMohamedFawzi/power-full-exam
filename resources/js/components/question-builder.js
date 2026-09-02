/**
 * Teacher-facing question builder. Talks to the QuestionController JSON
 * endpoints via window.aegis.request. Registered as x-data="questionBuilder".
 */
const emptyForm = () => ({
    id: null,
    type: 'multiple_choice',
    prompt: '',
    options: ['', ''],
    correct_answer: [],
    explanation: '',
    points: 1,
});

export default ({ examId, questions, locked, routes }) => ({
    examId,
    locked,
    routes,
    questions,
    form: emptyForm(),
    saving: false,
    error: null,
    modalOpen: false,

    init() {
        this.questions.sort((a, b) => a.position - b.position);
    },

    get isEditing() {
        return this.form.id !== null;
    },

    openAdd() {
        this.form = emptyForm();
        this.error = null;
        this.modalOpen = true;
        const el = this.$refs.modal || document.getElementById('question-modal');
        el?.showModal();
    },

    openEdit(question) {
        this.form = {
            id: question.id,
            type: question.type,
            prompt: question.prompt,
            options: question.options?.length ? [...question.options] : ['', ''],
            correct_answer: [...(question.correct_answer ?? [])],
            explanation: question.explanation ?? '',
            points: question.points,
        };
        this.error = null;
        this.modalOpen = true;
        const el = this.$refs.modal || document.getElementById('question-modal');
        el?.showModal();
    },

    close() {
        this.modalOpen = false;
        const el = this.$refs.modal || document.getElementById('question-modal');
        el?.close();
    },

    onTypeChange() {
        if (this.form.type === 'true_false') {
            this.form.options = ['صحيح', 'خطأ'];
            this.form.correct_answer = [0];
        } else if (this.form.type === 'short_answer') {
            this.form.options = [];
            this.form.correct_answer = [''];
        } else if (this.form.correct_answer.length === 0) {
            this.form.correct_answer = this.form.type === 'multiple_select' ? [] : [0];
        }
    },

    addOption() {
        this.form.options.push('');
    },

    removeOption(index) {
        this.form.options.splice(index, 1);
        this.form.correct_answer = this.form.correct_answer.filter((i) => i !== index);
    },

    isChecked(index) {
        return this.form.correct_answer.includes(index);
    },

    toggleMultiSelect(index) {
        this.form.correct_answer = this.isChecked(index)
            ? this.form.correct_answer.filter((i) => i !== index)
            : [...this.form.correct_answer, index];
    },

    setSingleCorrect(index) {
        this.form.correct_answer = [index];
    },

    async save() {
        this.saving = true;
        this.error = null;

        try {
            const payload = { ...this.form };
            delete payload.id;

            const response = this.isEditing
                ? await window.aegis.request(`${this.routes.questions}/${this.form.id}`, { method: 'PUT', body: payload })
                : await window.aegis.request(this.routes.questions, { method: 'POST', body: payload });

            if (this.isEditing) {
                const index = this.questions.findIndex((q) => q.id === this.form.id);
                if (index !== -1) this.questions[index] = response.question;
            } else {
                this.questions.push(response.question);
            }

            window.aegis.toast('تم حفظ السؤال بنجاح.', 'success');
            this.close();
        } catch (e) {
            this.error = e.payload?.message ?? 'تعذّر حفظ السؤال.';
        } finally {
            this.saving = false;
        }
    },

    async remove(question) {
        if (!confirm('هل أنت متأكد من حذف هذا السؤال؟')) return;

        try {
            await window.aegis.request(`${this.routes.questions}/${question.id}`, { method: 'DELETE' });
            this.questions = this.questions.filter((q) => q.id !== question.id);
            window.aegis.toast('تم حذف السؤال.', 'success');
        } catch (e) {
            window.aegis.toast(e.payload?.message ?? 'تعذّر حذف السؤال.', 'error');
        }
    },

    async duplicate(question) {
        try {
            const response = await window.aegis.request(`${this.routes.questions}/${question.id}/duplicate`, { method: 'POST' });
            this.questions.push(response.question);
            window.aegis.toast('تم تكرار السؤال.', 'success');
        } catch (e) {
            window.aegis.toast(e.payload?.message ?? 'تعذّر تكرار السؤال.', 'error');
        }
    },

    async move(question, direction) {
        const index = this.questions.findIndex((q) => q.id === question.id);
        const swapWith = index + direction;
        if (swapWith < 0 || swapWith >= this.questions.length) return;

        [this.questions[index], this.questions[swapWith]] = [this.questions[swapWith], this.questions[index]];

        try {
            await window.aegis.request(this.routes.reorder, {
                method: 'PUT',
                body: { question_ids: this.questions.map((q) => q.id) },
            });
            this.questions.forEach((q, i) => { q.position = i + 1; });
        } catch (e) {
            window.aegis.toast(e.payload?.message ?? 'تعذّر إعادة الترتيب.', 'error');
        }
    },
});
