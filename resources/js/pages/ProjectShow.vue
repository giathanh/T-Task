<script setup>
import { computed } from 'vue';

const props = defineProps({
    project: { type: Object, required: true },
    issueStats: { type: Object, required: true },
    members: { type: Array, required: true },
    currentUserRole: { type: String, default: null },
    newIssueUrl: { type: String, required: true },
});

const projectStatusLabels = {
    planning: 'Lên kế hoạch',
    in_progress: 'Đang thực hiện',
    on_hold: 'Tạm dừng',
    completed: 'Hoàn thành',
    archived: 'Lưu trữ',
};

const issueStatusLabels = {
    open: 'Chưa làm',
    in_progress: 'Đang làm',
    done: 'Hoàn thành',
};

const roleLabels = {
    admin: 'Admin',
    leader: 'Leader',
    member: 'Thành viên',
};

const roleBadgeClasses = {
    admin: 'bg-error-container text-on-error-container',
    leader: 'bg-tertiary-container text-on-tertiary-container',
    member: 'bg-surface-container-high text-on-surface-variant',
};

const canManageMembers = computed(() => ['admin', 'leader'].includes(props.currentUserRole));

const isOverdue = computed(() => {
    if (!props.project.dueDate || ['completed', 'archived'].includes(props.project.status)) {
        return false;
    }

    return new Date(props.project.dueDate) < new Date(new Date().toDateString());
});

function formatDate(value) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString('vi-VN');
}

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(-2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

const issueCards = computed(() => [
    {
        key: 'task',
        title: 'Tasks',
        icon: '📋',
        total: props.issueStats.task.total,
        byStatus: props.issueStats.task.by_status,
    },
    {
        key: 'bug',
        title: 'Bugs',
        icon: '🐛',
        total: props.issueStats.bug.total,
        byStatus: props.issueStats.bug.by_status,
    },
]);
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Project header -->
        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <p class="text-on-surface-variant">
                        {{ project.description || 'Chưa có mô tả' }}
                    </p>
                </div>

                <span class="shrink-0 rounded-full bg-primary-container px-3 py-1 text-sm font-medium text-on-primary-container">
                    {{ projectStatusLabels[project.status] ?? project.status }}
                </span>
            </div>

            <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant">
                <div v-if="project.creator" class="flex gap-1">
                    <dt class="font-medium">Owner:</dt>
                    <dd>{{ project.creator.name }}</dd>
                </div>
                <div class="flex gap-1">
                    <dt class="font-medium">Tạo:</dt>
                    <dd>{{ formatDate(project.createdAt) }}</dd>
                </div>
                <div v-if="project.dueDate" class="flex gap-1" :class="{ 'text-error': isOverdue }">
                    <dt class="font-medium">Deadline:</dt>
                    <dd>{{ formatDate(project.dueDate) }}</dd>
                </div>
            </dl>
        </section>

        <!-- Issues header -->
        <div class="flex items-center justify-between">
            <h3 class="font-medium text-on-surface">Issues</h3>
            <a
                :href="newIssueUrl"
                class="rounded-full bg-primary px-4 py-1.5 text-sm font-medium text-on-primary"
            >
                + New Task
            </a>
        </div>

        <!-- Stat cards -->
        <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div
                v-for="card in issueCards"
                :key="card.key"
                class="flex flex-col gap-3 rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1"
            >
                <p class="flex items-center gap-2 font-medium text-on-surface">
                    <span aria-hidden="true">{{ card.icon }}</span>
                    {{ card.title }}
                </p>
                <p class="text-3xl font-semibold text-on-surface">{{ card.total }}</p>
                <ul class="flex flex-col gap-1 text-sm text-on-surface-variant">
                    <li v-for="(count, status) in card.byStatus" :key="status">
                        {{ issueStatusLabels[status] ?? status }}: {{ count }}
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-3 rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
                <p class="flex items-center gap-2 font-medium text-on-surface">
                    <span aria-hidden="true">👥</span>
                    Members
                </p>
                <p class="text-3xl font-semibold text-on-surface">{{ members.length }}</p>
                <p class="text-sm text-on-surface-variant">người tham gia dự án</p>
            </div>
        </section>

        <!-- Members list -->
        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-medium text-on-surface">Thành viên ({{ members.length }})</h3>
                <button
                    v-if="canManageMembers"
                    type="button"
                    class="rounded-full bg-primary px-4 py-1.5 text-sm font-medium text-on-primary"
                >
                    + Invite
                </button>
            </div>

            <ul class="flex flex-col divide-y divide-outline-variant">
                <li v-for="member in members" :key="member.id" class="flex items-center justify-between gap-4 py-3">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary-container text-sm font-medium text-on-secondary-container"
                        >
                            {{ initials(member.name) }}
                        </span>
                        <div class="flex flex-col">
                            <span class="text-on-surface">{{ member.name }}</span>
                            <span class="text-sm text-on-surface-variant">{{ member.email }}</span>
                        </div>
                    </div>

                    <span class="rounded-full px-3 py-1 text-sm font-medium" :class="roleBadgeClasses[member.role]">
                        {{ roleLabels[member.role] ?? member.role }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
