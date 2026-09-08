import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import { compileScript, parse } from '@vue/compiler-sfc';
import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';

const source = await readFile(new URL('../../resources/js/pages/ProjectShow.vue', import.meta.url), 'utf8');
const { descriptor } = parse(source);
const compiled = compileScript(descriptor, { id: 'project-show-test', inlineTemplate: true });
const code = compiled.content.replaceAll('from "vue"', `from ${JSON.stringify(import.meta.resolve('vue'))}`)
    .replaceAll("from 'vue'", `from ${JSON.stringify(import.meta.resolve('vue'))}`);
const { default: ProjectShow } = await import(`data:text/javascript;base64,${Buffer.from(code).toString('base64')}`);

for (const timezone of ['Asia/Ho_Chi_Minh', 'America/Los_Angeles']) {
    for (const [dueDate, status, overdue] of [
        ['2026-09-08', 'in_progress', false],
        ['2026-09-07', 'in_progress', true],
        ['2026-09-09', 'in_progress', false],
        ['2026-09-07', 'completed', false],
        ['2026-09-07', 'archived', false],
        [null, 'planning', false],
    ]) {
        test(`${timezone}: deadline ${dueDate}, ${status}`, async (context) => {
            const originalTimezone = process.env.TZ;
            process.env.TZ = timezone;
            context.after(() => {
                if (originalTimezone === undefined) {
                    delete process.env.TZ;
                } else {
                    process.env.TZ = originalTimezone;
                }
            });
            context.mock.timers.enable({ apis: ['Date'], now: new Date('2026-09-08T12:00:00').getTime() });

            const html = await renderToString(createSSRApp(ProjectShow, {
                project: { description: 'Project', status, dueDate, createdAt: '2026-09-08' },
                issueStats: { task: { total: 0, by_status: {} }, bug: { total: 0, by_status: {} } },
                members: [],
                newIssueUrl: '/issues/create',
            }));

            assert.ok(html.includes('8/9/2026'));
            assert.equal(/class="[^"]*text-error[^"]*"/.test(html), overdue);
            assert.equal(html.includes('Deadline:'), dueDate !== null);
        });
    }
}
