const assert = require('assert');
const fs = require('fs');
const path = require('path');
const querystring = require('querystring');
const vm = require('vm');

const source = fs.readFileSync(path.join(__dirname, '../../js/multinzb.js'), 'utf8');
const sandbox = {
    setTimeout: function(callback) {
        callback();
    },
};

vm.runInNewContext(source, sandbox);

const helper = sandbox.spotwebMultiNzb;

assert.strictEqual(helper.endpoint, '?');

function makeMessageIds(count) {
    const messageIds = [];

    for (let i = 0; i < count; i++) {
        messageIds.push(`<spot-${String(i).padStart(3, '0')}@example.invalid>`);
    }

    return messageIds;
}

assert.strictEqual(helper.normalizeAction('display'), 'display');
assert.strictEqual(helper.normalizeAction('push-sabnzbd'), 'push-sabnzbd');
assert.strictEqual(helper.normalizeAction('client-sabnzbd'), 'display');
assert.strictEqual(helper.normalizeAction('disable'), 'display');

const messageIds = makeMessageIds(2000);
const fields = helper.buildFields('push-sabnzbd', messageIds);
assert.strictEqual(fields.length, 3);
assert.strictEqual(fields[0].name, 'page');
assert.strictEqual(fields[0].value, 'getnzb');
assert.strictEqual(fields[1].name, 'action');
assert.strictEqual(fields[1].value, 'push-sabnzbd');
assert.strictEqual(fields[2].name, 'messageids');
assert.deepStrictEqual(JSON.parse(fields[2].value), messageIds);

const body = helper.buildRequestBody('push-sabnzbd', messageIds);
assert.ok(!body.startsWith('?'), 'POST body must not be a URL query string');
assert.ok(!body.includes('?page=getnzb'), 'POST body must not include a request URL');

const parsed = querystring.parse(body);
assert.strictEqual(parsed.page, 'getnzb');
assert.strictEqual(parsed.action, 'push-sabnzbd');
assert.strictEqual(Object.keys(parsed).length, 3);
assert.deepStrictEqual(JSON.parse(parsed.messageids), messageIds);

const displayFields = helper.buildFields('client-sabnzbd', messageIds);
assert.strictEqual(displayFields[1].value, 'display');

const submittedForms = [];
const documentStub = {
    body: {
        children: [],
        appendChild: function(element) {
            element.parentNode = this;
            this.children.push(element);
        },
        removeChild: function(element) {
            this.children = this.children.filter((child) => child !== element);
            element.parentNode = null;
        },
    },
    createElement: function(tagName) {
        return {
            tagName: tagName,
            children: [],
            style: {},
            appendChild: function(element) {
                this.children.push(element);
            },
            submit: function() {
                submittedForms.push(this);
            },
        };
    },
};

helper.submitDisplay('client-sabnzbd', messageIds, documentStub);

assert.strictEqual(submittedForms.length, 1);
assert.strictEqual(submittedForms[0].method, 'post');
assert.strictEqual(submittedForms[0].action, '?');
assert.strictEqual(submittedForms[0].children[0].name, 'page');
assert.strictEqual(submittedForms[0].children[0].value, 'getnzb');
assert.strictEqual(submittedForms[0].children[1].name, 'action');
assert.strictEqual(submittedForms[0].children[1].value, 'display');
assert.strictEqual(submittedForms[0].children.length, 3);
assert.strictEqual(submittedForms[0].children[2].name, 'messageids');
assert.deepStrictEqual(JSON.parse(submittedForms[0].children[2].value), messageIds);
assert.strictEqual(documentStub.body.children.length, 0);

console.log('multinzb helper tests passed');
