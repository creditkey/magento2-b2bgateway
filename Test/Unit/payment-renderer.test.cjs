const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function renderer({reject = false, valid = true} = {}) {
    let component;
    let modal = null;
    let messageHandler;
    const timers = [];
    const calls = [];
    const payment = {
        creditkey_gateway: {redirectUrl: '/create', checkoutMode: 'modal', isCreditKeyDisplayed: true},
        creditkey_pay_by_bank: {redirectUrl: '/create', checkoutMode: 'redirect', isCreditKeyDisplayed: true}
    };
    const jquery = () => ({livequery() {}});
    const setPayment = (messages, data) => {
        calls.push({type: 'save', method: data.method, messages});
        const promise = reject ? Promise.reject(new Error('Payment rejected')) : Promise.resolve();
        return {then: fn => ({always: cleanup => promise.then(fn).finally(cleanup)})};
    };
    const sdk = {Client: function () {}, checkout: (url, mode) => calls.push({type: 'checkout', url, mode})};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../view/frontend/web/js/view/payment/method-renderer/creditkey_gateway.js'), 'utf8'), {
        window: {checkoutConfig: {payment}, location: {origin: 'https://merchant.example'},
            addEventListener: (type, handler) => { messageHandler = handler; }},
        document: {getElementById: () => modal},
        setTimeout: callback => timers.push(callback),
        define: (dependencies, factory) => {
            component = factory(jquery, {extend: value => value}, data => calls.push({type: 'select', method: data.method}),
                {setSelectedPaymentMethod: () => {}}, {validate: () => valid}, setPayment, sdk);
        }
    });
    function instance(code) {
        let allowed = true;
        return Object.assign(Object.create(component), {item: {method: code}, messageContainer: {},
            validate: () => true,
            isPlaceOrderActionAllowed: (...args) => args.length ? (allowed = args[0]) : allowed});
    }
    return {instance, calls, setModal: value => { modal = value; },
        close: () => messageHandler({origin: 'https://merchant.example',
            data: {event: 'creditkey_b2b_gateway', action: 'closePopup'}}),
        runTimers: () => timers.splice(0).forEach(callback => callback())};
}

test('each renderer saves its own method and uses its configured mode when switching', async () => {
    const {instance, calls} = renderer();
    for (const [code, mode] of [['creditkey_pay_by_bank', 'redirect'], ['creditkey_gateway', 'modal']]) {
        const method = instance(code);
        method.selectPaymentMethod();
        const result = method.redirectToPayment();
        assert.equal(method.isPlaceOrderActionAllowed(), false);
        await result;
        assert.equal(method.isPlaceOrderActionAllowed(), true);
        const [select, save, checkout] = calls.splice(0);
        assert.equal(select.method, code);
        assert.equal(save.method, code);
        assert.equal(save.messages, method.messageContainer);
        assert.equal(checkout.mode, mode);
        assert.match(checkout.url, /^\/create\?_ck_attempt=\d+$/);
    }
});

test('a failed save never opens checkout and permits retry', async () => {
    const {instance, calls} = renderer({reject: true});
    const method = instance('creditkey_pay_by_bank');
    await assert.rejects(method.redirectToPayment(), /Payment rejected/);
    assert.equal(method.isPlaceOrderActionAllowed(), true);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].type, 'save');
});

test('invalid checkout forms do not send a request', () => {
    const {instance, calls} = renderer({valid: false});
    assert.equal(instance('creditkey_pay_by_bank').redirectToPayment(), false);
    assert.equal(calls.length, 0);
});

test('repeat clicks while saving cannot create another checkout request', async () => {
    const {instance, calls} = renderer();
    const method = instance('creditkey_pay_by_bank');
    const pending = method.redirectToPayment();
    assert.equal(method.redirectToPayment(), false);
    await pending;
    assert.equal(calls.filter(call => call.type === 'save').length, 1);
});

test('a successful save discards the previous modal before launching a different method', async () => {
    const {instance, calls, setModal} = renderer();
    setModal({remove: () => calls.push({type: 'remove'})});
    await instance('creditkey_pay_by_bank').redirectToPayment();
    assert.deepEqual(calls.map(call => call.type), ['save', 'remove', 'checkout']);
});

test('a failed save leaves the previous modal alone', async () => {
    const {instance, calls, setModal} = renderer({reject: true});
    setModal({remove: () => calls.push({type: 'remove'})});
    await assert.rejects(instance('creditkey_pay_by_bank').redirectToPayment());
    assert.deepEqual(calls.map(call => call.type), ['save']);
});

test('a delayed close message cannot remove a newly opened modal', () => {
    const {setModal, close, runTimers} = renderer();
    const removed = [];
    setModal({remove: () => removed.push('old')});
    close();
    setModal({remove: () => removed.push('new')});
    runTimers();
    assert.deepEqual(removed, ['old']);
});
