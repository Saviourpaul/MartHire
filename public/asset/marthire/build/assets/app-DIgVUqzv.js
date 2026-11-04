const __vite__mapDeps = (
    i,
    m = __vite__mapDeps,
    d = m.f ||
        (m.f = [
            "assets/candidate-pdf-Bk76uDWt.js",
            "assets/pdf.worker.min-BrZ5xJmk.js",
            "assets/candidate-resume-autofill-CBxBDr2u.js",
        ]),
) => i.map((i) => d[i]);
var Uu = (t, e) => () => (e || t((e = { exports: {} }).exports, e), e.exports);
var K_ = Uu((Ue, Fe) => {
    const Fu = "modulepreload",
        Hu = function (t) {
            return "/asset/marthire/build/" + t;
        },
        hl = {},
        pa = function (e, r, n) {
            let s = Promise.resolve();
            if (r && r.length > 0) {
                let f = function (u) {
                    return Promise.all(
                        u.map((d) =>
                            Promise.resolve(d).then(
                                (h) => ({ status: "fulfilled", value: h }),
                                (h) => ({ status: "rejected", reason: h }),
                            ),
                        ),
                    );
                };
                var o = f;
                document.getElementsByTagName("link");
                const a = document.querySelector("meta[property=csp-nonce]"),
                    l = a?.nonce || a?.getAttribute("nonce");
                s = f(
                    r.map((u) => {
                        if (((u = Hu(u)), u in hl)) return;
                        hl[u] = !0;
                        const d = u.endsWith(".css"),
                            h = d ? '[rel="stylesheet"]' : "";
                        if (document.querySelector(`link[href="${u}"]${h}`))
                            return;
                        const g = document.createElement("link");
                        if (
                            ((g.rel = d ? "stylesheet" : Fu),
                            d || (g.as = "script"),
                            (g.crossOrigin = ""),
                            (g.href = u),
                            l && g.setAttribute("nonce", l),
                            document.head.appendChild(g),
                            d)
                        )
                            return new Promise((v, b) => {
                                (g.addEventListener("load", v),
                                    g.addEventListener("error", () =>
                                        b(
                                            new Error(
                                                `Unable to preload CSS for ${u}`,
                                            ),
                                        ),
                                    ));
                            });
                    }),
                );
            }
            function i(a) {
                const l = new Event("vite:preloadError", { cancelable: !0 });
                if (
                    ((l.payload = a),
                    window.dispatchEvent(l),
                    !l.defaultPrevented)
                )
                    throw a;
            }
            return s.then((a) => {
                for (const l of a || []) l.status === "rejected" && i(l.reason);
                return e().catch(i);
            });
        };
    function X0(t, e) {
        return function () {
            return t.apply(e, arguments);
        };
    }
    const { toString: zu } = Object.prototype,
        { getPrototypeOf: Gr } = Object,
        { iterator: Zn, toStringTag: J0 } = Symbol,
        zs = (
            ({ hasOwnProperty: t }) =>
            (e, r) =>
                t.call(e, r)
        )(Object.prototype),
        Dn = (t, e) => {
            let r = t;
            const n = [];
            for (; r != null && r !== Object.prototype; ) {
                if (n.indexOf(r) !== -1) return !1;
                if ((n.push(r), zs(r, e))) return !0;
                r = Gr(r);
            }
            return !1;
        },
        Ku = (t, e) => (t != null && Dn(t, e) ? t[e] : void 0),
        ga = ((t) => (e) => {
            const r = zu.call(e);
            return t[r] || (t[r] = r.slice(8, -1).toLowerCase());
        })(Object.create(null)),
        ot = (t) => ((t = t.toLowerCase()), (e) => ga(e) === t),
        ni = (t) => (e) => typeof e === t,
        { isArray: ur } = Array,
        Zr = ni("undefined");
    function Qr(t) {
        return (
            t !== null &&
            !Zr(t) &&
            t.constructor !== null &&
            !Zr(t.constructor) &&
            je(t.constructor.isBuffer) &&
            t.constructor.isBuffer(t)
        );
    }
    const Y0 = ot("ArrayBuffer");
    function Wu(t) {
        let e;
        return (
            typeof ArrayBuffer < "u" && ArrayBuffer.isView
                ? (e = ArrayBuffer.isView(t))
                : (e = t && t.buffer && Y0(t.buffer)),
            e
        );
    }
    const Vu = ni("string"),
        je = ni("function"),
        Q0 = ni("number"),
        en = (t) => t !== null && typeof t == "object",
        Gu = (t) => t === !0 || t === !1,
        Ds = (t) => {
            if (!en(t)) return !1;
            const e = Gr(t);
            return (
                (e === null || e === Object.prototype || Gr(e) === null) &&
                !Dn(t, J0) &&
                !Dn(t, Zn)
            );
        },
        Zu = (t) => {
            if (!en(t) || Qr(t)) return !1;
            try {
                return (
                    Object.keys(t).length === 0 &&
                    Object.getPrototypeOf(t) === Object.prototype
                );
            } catch {
                return !1;
            }
        },
        Xu = ot("Date"),
        Ju = ot("File"),
        Yu = (t) => !!(t && typeof t.uri < "u"),
        Qu = (t) => t && typeof t.getParts < "u",
        eh = ot("Blob"),
        th = ot("FileList"),
        rh = (t) => en(t) && je(t.pipe);
    function nh() {
        return typeof globalThis < "u"
            ? globalThis
            : typeof self < "u"
              ? self
              : typeof window < "u"
                ? window
                : typeof global < "u"
                  ? global
                  : {};
    }
    const dl = nh(),
        pl = typeof dl.FormData < "u" ? dl.FormData : void 0,
        sh = (t) => {
            if (!t) return !1;
            if (pl && t instanceof pl) return !0;
            const e = Gr(t);
            if (!e || e === Object.prototype || !je(t.append)) return !1;
            const r = ga(t);
            return (
                r === "formdata" ||
                (r === "object" &&
                    je(t.toString) &&
                    t.toString() === "[object FormData]")
            );
        },
        ih = ot("URLSearchParams"),
        [oh, ah, lh, fh] = [
            "ReadableStream",
            "Request",
            "Response",
            "Headers",
        ].map(ot),
        ch = (t) =>
            t.trim
                ? t.trim()
                : t.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, "");
    function Xn(t, e, { allOwnKeys: r = !1 } = {}) {
        if (t === null || typeof t > "u") return;
        let n, s;
        if ((typeof t != "object" && (t = [t]), ur(t)))
            for (n = 0, s = t.length; n < s; n++) e.call(null, t[n], n, t);
        else {
            if (Qr(t)) return;
            const i = r ? Object.getOwnPropertyNames(t) : Object.keys(t),
                o = i.length;
            let a;
            for (n = 0; n < o; n++) ((a = i[n]), e.call(null, t[a], a, t));
        }
    }
    function ef(t, e) {
        if (Qr(t)) return null;
        e = e.toLowerCase();
        const r = Object.keys(t);
        let n = r.length,
            s;
        for (; n-- > 0; ) if (((s = r[n]), e === s.toLowerCase())) return s;
        return null;
    }
    const sr =
            typeof globalThis < "u"
                ? globalThis
                : typeof self < "u"
                  ? self
                  : typeof window < "u"
                    ? window
                    : global,
        tf = (t) => !Zr(t) && t !== sr;
    function Ro(...t) {
        const { caseless: e, skipUndefined: r } = (tf(this) && this) || {},
            n = {},
            s = (i, o) => {
                if (
                    o === "__proto__" ||
                    o === "constructor" ||
                    o === "prototype"
                )
                    return;
                const a = (e && typeof o == "string" && ef(n, o)) || o,
                    l = zs(n, a) ? n[a] : void 0;
                Ds(l) && Ds(i)
                    ? (n[a] = Ro(l, i))
                    : Ds(i)
                      ? (n[a] = Ro({}, i))
                      : ur(i)
                        ? (n[a] = i.slice())
                        : (!r || !Zr(i)) && (n[a] = i);
            };
        for (let i = 0, o = t.length; i < o; i++) {
            const a = t[i];
            if (!a || Qr(a) || (Xn(a, s), typeof a != "object" || ur(a)))
                continue;
            const l = Object.getOwnPropertySymbols(a);
            for (let f = 0; f < l.length; f++) {
                const u = l[f];
                _h.call(a, u) && s(a[u], u);
            }
        }
        return n;
    }
    const uh = (t, e, r, { allOwnKeys: n } = {}) => (
            Xn(
                e,
                (s, i) => {
                    r && je(s)
                        ? Object.defineProperty(t, i, {
                              __proto__: null,
                              value: X0(s, r),
                              writable: !0,
                              enumerable: !0,
                              configurable: !0,
                          })
                        : Object.defineProperty(t, i, {
                              __proto__: null,
                              value: s,
                              writable: !0,
                              enumerable: !0,
                              configurable: !0,
                          });
                },
                { allOwnKeys: n },
            ),
            t
        ),
        hh = (t) => (t.charCodeAt(0) === 65279 && (t = t.slice(1)), t),
        dh = (t, e, r, n) => {
            ((t.prototype = Object.create(e.prototype, n)),
                Object.defineProperty(t.prototype, "constructor", {
                    __proto__: null,
                    value: t,
                    writable: !0,
                    enumerable: !1,
                    configurable: !0,
                }),
                Object.defineProperty(t, "super", {
                    __proto__: null,
                    value: e.prototype,
                }),
                r && Object.assign(t.prototype, r));
        },
        ph = (t, e, r, n) => {
            let s, i, o;
            const a = {};
            if (((e = e || {}), t == null)) return e;
            do {
                for (s = Object.getOwnPropertyNames(t), i = s.length; i-- > 0; )
                    ((o = s[i]),
                        (!n || n(o, t, e)) &&
                            !a[o] &&
                            ((e[o] = t[o]), (a[o] = !0)));
                t = r !== !1 && Gr(t);
            } while (t && (!r || r(t, e)) && t !== Object.prototype);
            return e;
        },
        gh = (t, e, r) => {
            ((t = String(t)),
                (r === void 0 || r > t.length) && (r = t.length),
                (r -= e.length));
            const n = t.indexOf(e, r);
            return n !== -1 && n === r;
        },
        mh = (t) => {
            if (!t) return null;
            if (ur(t)) return t;
            let e = t.length;
            if (!Q0(e)) return null;
            const r = new Array(e);
            for (; e-- > 0; ) r[e] = t[e];
            return r;
        },
        bh = (
            (t) => (e) =>
                t && e instanceof t
        )(typeof Uint8Array < "u" && Gr(Uint8Array)),
        yh = (t, e) => {
            const n = (t && t[Zn]).call(t);
            let s;
            for (; (s = n.next()) && !s.done; ) {
                const i = s.value;
                e.call(t, i[0], i[1]);
            }
        },
        vh = (t, e) => {
            let r;
            const n = [];
            for (; (r = t.exec(e)) !== null; ) n.push(r);
            return n;
        },
        wh = ot("HTMLFormElement"),
        Eh = (t) =>
            t
                .toLowerCase()
                .replace(/[-_\s]([a-z\d])(\w*)/g, function (r, n, s) {
                    return n.toUpperCase() + s;
                }),
        { propertyIsEnumerable: _h } = Object.prototype,
        xh = ot("RegExp"),
        rf = (t, e) => {
            const r = Object.getOwnPropertyDescriptors(t),
                n = {};
            (Xn(r, (s, i) => {
                let o;
                (o = e(s, i, t)) !== !1 && (n[i] = o || s);
            }),
                Object.defineProperties(t, n));
        },
        Ah = (t) => {
            rf(t, (e, r) => {
                if (je(t) && ["arguments", "caller", "callee"].includes(r))
                    return !1;
                const n = t[r];
                if (je(n)) {
                    if (((e.enumerable = !1), "writable" in e)) {
                        e.writable = !1;
                        return;
                    }
                    e.set ||
                        (e.set = () => {
                            throw Error(
                                "Can not rewrite read-only method '" + r + "'",
                            );
                        });
                }
            });
        },
        Sh = (t, e) => {
            const r = {},
                n = (s) => {
                    s.forEach((i) => {
                        r[i] = !0;
                    });
                };
            return (ur(t) ? n(t) : n(String(t).split(e)), r);
        },
        Th = () => {},
        Nh = (t, e) => (t != null && Number.isFinite((t = +t)) ? t : e);
    function Oh(t) {
        return !!(t && je(t.append) && t[J0] === "FormData" && t[Zn]);
    }
    const Ch = (t) => {
            const e = new WeakSet(),
                r = (n) => {
                    if (en(n)) {
                        if (e.has(n)) return;
                        if (Qr(n)) return n;
                        if (!("toJSON" in n)) {
                            e.add(n);
                            const s = ur(n) ? [] : {};
                            return (
                                Xn(n, (i, o) => {
                                    const a = r(i);
                                    !Zr(a) && (s[o] = a);
                                }),
                                e.delete(n),
                                s
                            );
                        }
                    }
                    return n;
                };
            return r(t);
        },
        Lh = ot("AsyncFunction"),
        Rh = (t) => t && (en(t) || je(t)) && je(t.then) && je(t.catch),
        nf = ((t, e) =>
            t
                ? setImmediate
                : e
                  ? ((r, n) => (
                        sr.addEventListener(
                            "message",
                            ({ source: s, data: i }) => {
                                s === sr && i === r && n.length && n.shift()();
                            },
                            !1,
                        ),
                        (s) => {
                            (n.push(s), sr.postMessage(r, "*"));
                        }
                    ))(`axios@${Math.random()}`, [])
                  : (r) => setTimeout(r))(
            typeof setImmediate == "function",
            je(sr.postMessage),
        ),
        qh =
            typeof queueMicrotask < "u"
                ? queueMicrotask.bind(sr)
                : (typeof process < "u" && process.nextTick) || nf,
        sf = (t) => t != null && je(t[Zn]),
        kh = (t) => t != null && Dn(t, Zn) && sf(t),
        x = {
            isArray: ur,
            isArrayBuffer: Y0,
            isBuffer: Qr,
            isFormData: sh,
            isArrayBufferView: Wu,
            isString: Vu,
            isNumber: Q0,
            isBoolean: Gu,
            isObject: en,
            isPlainObject: Ds,
            isEmptyObject: Zu,
            isReadableStream: oh,
            isRequest: ah,
            isResponse: lh,
            isHeaders: fh,
            isUndefined: Zr,
            isDate: Xu,
            isFile: Ju,
            isReactNativeBlob: Yu,
            isReactNative: Qu,
            isBlob: eh,
            isRegExp: xh,
            isFunction: je,
            isStream: rh,
            isURLSearchParams: ih,
            isTypedArray: bh,
            isFileList: th,
            forEach: Xn,
            merge: Ro,
            extend: uh,
            trim: ch,
            stripBOM: hh,
            inherits: dh,
            toFlatObject: ph,
            kindOf: ga,
            kindOfTest: ot,
            endsWith: gh,
            toArray: mh,
            forEachEntry: yh,
            matchAll: vh,
            isHTMLForm: wh,
            hasOwnProperty: zs,
            hasOwnProp: zs,
            hasOwnInPrototypeChain: Dn,
            getSafeProp: Ku,
            reduceDescriptors: rf,
            freezeMethods: Ah,
            toObjectSet: Sh,
            toCamelCase: Eh,
            noop: Th,
            toFiniteNumber: Nh,
            findKey: ef,
            global: sr,
            isContextDefined: tf,
            isSpecCompliantForm: Oh,
            toJSONObject: Ch,
            isAsyncFn: Lh,
            isThenable: Rh,
            setImmediate: nf,
            asap: qh,
            isIterable: sf,
            isSafeIterable: kh,
        },
        Ih = x.toObjectSet([
            "age",
            "authorization",
            "content-length",
            "content-type",
            "etag",
            "expires",
            "from",
            "host",
            "if-modified-since",
            "if-unmodified-since",
            "last-modified",
            "location",
            "max-forwards",
            "proxy-authorization",
            "referer",
            "retry-after",
            "user-agent",
        ]),
        Ph = (t) => {
            const e = {};
            let r, n, s;
            return (
                t &&
                    t
                        .split(
                            `
`,
                        )
                        .forEach(function (o) {
                            ((s = o.indexOf(":")),
                                (r = o.substring(0, s).trim().toLowerCase()),
                                (n = o.substring(s + 1).trim()),
                                !(!r || (e[r] && Ih[r])) &&
                                    (r === "set-cookie"
                                        ? e[r]
                                            ? e[r].push(n)
                                            : (e[r] = [n])
                                        : (e[r] = e[r] ? e[r] + ", " + n : n)));
                        }),
                e
            );
        };
    function Mh(t) {
        let e = 0,
            r = t.length;
        for (; e < r; ) {
            const n = t.charCodeAt(e);
            if (n !== 9 && n !== 32) break;
            e += 1;
        }
        for (; r > e; ) {
            const n = t.charCodeAt(r - 1);
            if (n !== 9 && n !== 32) break;
            r -= 1;
        }
        return e === 0 && r === t.length ? t : t.slice(e, r);
    }
    const Bh = new RegExp("[\\u0000-\\u0008\\u000a-\\u001f\\u007f]+", "g"),
        $h = new RegExp("[^\\u0009\\u0020-\\u007e\\u0080-\\u00ff]+", "g");
    function ma(t, e) {
        return x.isArray(t)
            ? t.map((r) => ma(r, e))
            : Mh(String(t).replace(e, ""));
    }
    const Dh = (t) => ma(t, Bh),
        jh = (t) => ma(t, $h);
    function of(t) {
        const e = Object.create(null);
        return (
            x.forEach(t.toJSON(), (r, n) => {
                e[n] = jh(r);
            }),
            e
        );
    }
    const gl = Symbol("internals");
    function xn(t) {
        return t && String(t).trim().toLowerCase();
    }
    function js(t) {
        return t === !1 || t == null
            ? t
            : x.isArray(t)
              ? t.map(js)
              : Dh(String(t));
    }
    function Uh(t) {
        const e = Object.create(null),
            r = /([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;
        let n;
        for (; (n = r.exec(t)); ) e[n[1]] = n[2];
        return e;
    }
    const Fh = (t) => /^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(t.trim());
    function ao(t, e, r, n, s) {
        if (x.isFunction(n)) return n.call(this, e, r);
        if ((s && (e = r), !!x.isString(e))) {
            if (x.isString(n)) return e.indexOf(n) !== -1;
            if (x.isRegExp(n)) return n.test(e);
        }
    }
    function Hh(t) {
        return t
            .trim()
            .toLowerCase()
            .replace(/([a-z\d])(\w*)/g, (e, r, n) => r.toUpperCase() + n);
    }
    function zh(t, e) {
        const r = x.toCamelCase(" " + e);
        ["get", "set", "has"].forEach((n) => {
            Object.defineProperty(t, n + r, {
                __proto__: null,
                value: function (s, i, o) {
                    return this[n].call(this, e, s, i, o);
                },
                configurable: !0,
            });
        });
    }
    let ke = class {
        constructor(e) {
            e && this.set(e);
        }
        set(e, r, n) {
            const s = this;
            function i(a, l, f) {
                const u = xn(l);
                if (!u) return;
                const d = x.findKey(s, u);
                (!d ||
                    s[d] === void 0 ||
                    f === !0 ||
                    (f === void 0 && s[d] !== !1)) &&
                    (s[d || l] = js(a));
            }
            const o = (a, l) => x.forEach(a, (f, u) => i(f, u, l));
            if (x.isPlainObject(e) || e instanceof this.constructor) o(e, r);
            else if (x.isString(e) && (e = e.trim()) && !Fh(e)) o(Ph(e), r);
            else if (x.isObject(e) && x.isSafeIterable(e)) {
                let a = Object.create(null),
                    l,
                    f;
                for (const u of e) {
                    if (!x.isArray(u))
                        throw new TypeError(
                            "Object iterator must return a key-value pair",
                        );
                    ((f = u[0]),
                        x.hasOwnProp(a, f)
                            ? ((l = a[f]),
                              (a[f] = x.isArray(l) ? [...l, u[1]] : [l, u[1]]))
                            : (a[f] = u[1]));
                }
                o(a, r);
            } else e != null && i(r, e, n);
            return this;
        }
        get(e, r) {
            if (((e = xn(e)), e)) {
                const n = x.findKey(this, e);
                if (n) {
                    const s = this[n];
                    if (!r) return s;
                    if (r === !0) return Uh(s);
                    if (x.isFunction(r)) return r.call(this, s, n);
                    if (x.isRegExp(r)) return r.exec(s);
                    throw new TypeError(
                        "parser must be boolean|regexp|function",
                    );
                }
            }
        }
        has(e, r) {
            if (((e = xn(e)), e)) {
                const n = x.findKey(this, e);
                return !!(
                    n &&
                    this[n] !== void 0 &&
                    (!r || ao(this, this[n], n, r))
                );
            }
            return !1;
        }
        delete(e, r) {
            const n = this;
            let s = !1;
            function i(o) {
                if (((o = xn(o)), o)) {
                    const a = x.findKey(n, o);
                    a && (!r || ao(n, n[a], a, r)) && (delete n[a], (s = !0));
                }
            }
            return (x.isArray(e) ? e.forEach(i) : i(e), s);
        }
        clear(e) {
            const r = Object.keys(this);
            let n = r.length,
                s = !1;
            for (; n--; ) {
                const i = r[n];
                (!e || ao(this, this[i], i, e, !0)) &&
                    (delete this[i], (s = !0));
            }
            return s;
        }
        normalize(e) {
            const r = this,
                n = {};
            return (
                x.forEach(this, (s, i) => {
                    const o = x.findKey(n, i);
                    if (o) {
                        ((r[o] = js(s)), delete r[i]);
                        return;
                    }
                    const a = e ? Hh(i) : String(i).trim();
                    (a !== i && delete r[i], (r[a] = js(s)), (n[a] = !0));
                }),
                this
            );
        }
        concat(...e) {
            return this.constructor.concat(this, ...e);
        }
        toJSON(e) {
            const r = Object.create(null);
            return (
                x.forEach(this, (n, s) => {
                    n != null &&
                        n !== !1 &&
                        (r[s] = e && x.isArray(n) ? n.join(", ") : n);
                }),
                r
            );
        }
        [Symbol.iterator]() {
            return Object.entries(this.toJSON())[Symbol.iterator]();
        }
        toString() {
            return Object.entries(this.toJSON()).map(([e, r]) => e + ": " + r)
                .join(`
`);
        }
        getSetCookie() {
            return this.get("set-cookie") || [];
        }
        get [Symbol.toStringTag]() {
            return "AxiosHeaders";
        }
        static from(e) {
            return e instanceof this ? e : new this(e);
        }
        static concat(e, ...r) {
            const n = new this(e);
            return (r.forEach((s) => n.set(s)), n);
        }
        static accessor(e) {
            const n = (this[gl] = this[gl] = { accessors: {} }).accessors,
                s = this.prototype;
            function i(o) {
                const a = xn(o);
                n[a] || (zh(s, o), (n[a] = !0));
            }
            return (x.isArray(e) ? e.forEach(i) : i(e), this);
        }
    };
    ke.accessor([
        "Content-Type",
        "Content-Length",
        "Accept",
        "Accept-Encoding",
        "User-Agent",
        "Authorization",
    ]);
    x.reduceDescriptors(ke.prototype, ({ value: t }, e) => {
        let r = e[0].toUpperCase() + e.slice(1);
        return {
            get: () => t,
            set(n) {
                this[r] = n;
            },
        };
    });
    x.freezeMethods(ke);
    const Kh = "[REDACTED ****]";
    function Wh(t) {
        if (x.hasOwnProp(t, "toJSON")) return !0;
        let e = Object.getPrototypeOf(t);
        for (; e && e !== Object.prototype; ) {
            if (x.hasOwnProp(e, "toJSON")) return !0;
            e = Object.getPrototypeOf(e);
        }
        return !1;
    }
    function Vh(t, e) {
        const r = new Set(e.map((i) => String(i).toLowerCase())),
            n = [],
            s = (i) => {
                if (i === null || typeof i != "object" || x.isBuffer(i))
                    return i;
                if (n.indexOf(i) !== -1) return;
                (i instanceof ke && (i = i.toJSON()), n.push(i));
                let o;
                if (x.isArray(i))
                    ((o = []),
                        i.forEach((a, l) => {
                            const f = s(a);
                            x.isUndefined(f) || (o[l] = f);
                        }));
                else {
                    if (!x.isPlainObject(i) && Wh(i)) return (n.pop(), i);
                    o = Object.create(null);
                    for (const [a, l] of Object.entries(i)) {
                        const f = r.has(a.toLowerCase()) ? Kh : s(l);
                        x.isUndefined(f) || (o[a] = f);
                    }
                }
                return (n.pop(), o);
            };
        return s(t);
    }
    let j = class af extends Error {
        static from(e, r, n, s, i, o) {
            const a = new af(e.message, r || e.code, n, s, i);
            return (
                Object.defineProperty(a, "cause", {
                    __proto__: null,
                    value: e,
                    writable: !0,
                    enumerable: !1,
                    configurable: !0,
                }),
                (a.name = e.name),
                e.status != null && a.status == null && (a.status = e.status),
                o && Object.assign(a, o),
                a
            );
        }
        constructor(e, r, n, s, i) {
            (super(e),
                Object.defineProperty(this, "message", {
                    __proto__: null,
                    value: e,
                    enumerable: !0,
                    writable: !0,
                    configurable: !0,
                }),
                (this.name = "AxiosError"),
                (this.isAxiosError = !0),
                r && (this.code = r),
                n && (this.config = n),
                s && (this.request = s),
                i && ((this.response = i), (this.status = i.status)));
        }
        toJSON() {
            const e = this.config,
                r = e && x.hasOwnProp(e, "redact") ? e.redact : void 0,
                n = x.isArray(r) && r.length > 0 ? Vh(e, r) : x.toJSONObject(e);
            return {
                message: this.message,
                name: this.name,
                description: this.description,
                number: this.number,
                fileName: this.fileName,
                lineNumber: this.lineNumber,
                columnNumber: this.columnNumber,
                stack: this.stack,
                config: n,
                code: this.code,
                status: this.status,
            };
        }
    };
    j.ERR_BAD_OPTION_VALUE = "ERR_BAD_OPTION_VALUE";
    j.ERR_BAD_OPTION = "ERR_BAD_OPTION";
    j.ECONNABORTED = "ECONNABORTED";
    j.ETIMEDOUT = "ETIMEDOUT";
    j.ECONNREFUSED = "ECONNREFUSED";
    j.ERR_NETWORK = "ERR_NETWORK";
    j.ERR_FR_TOO_MANY_REDIRECTS = "ERR_FR_TOO_MANY_REDIRECTS";
    j.ERR_DEPRECATED = "ERR_DEPRECATED";
    j.ERR_BAD_RESPONSE = "ERR_BAD_RESPONSE";
    j.ERR_BAD_REQUEST = "ERR_BAD_REQUEST";
    j.ERR_CANCELED = "ERR_CANCELED";
    j.ERR_NOT_SUPPORT = "ERR_NOT_SUPPORT";
    j.ERR_INVALID_URL = "ERR_INVALID_URL";
    j.ERR_FORM_DATA_DEPTH_EXCEEDED = "ERR_FORM_DATA_DEPTH_EXCEEDED";
    const Gh = null,
        lf = 100;
    function qo(t) {
        return x.isPlainObject(t) || x.isArray(t);
    }
    function ff(t) {
        return x.endsWith(t, "[]") ? t.slice(0, -2) : t;
    }
    function lo(t, e, r) {
        return t
            ? t
                  .concat(e)
                  .map(function (s, i) {
                      return ((s = ff(s)), !r && i ? "[" + s + "]" : s);
                  })
                  .join(r ? "." : "")
            : e;
    }
    function Zh(t) {
        return x.isArray(t) && !t.some(qo);
    }
    const Xh = x.toFlatObject(x, {}, null, function (e) {
        return /^is[A-Z]/.test(e);
    });
    function si(t, e, r) {
        if (!x.isObject(t)) throw new TypeError("target must be an object");
        ((e = e || new FormData()),
            (r = x.toFlatObject(
                r,
                { metaTokens: !0, dots: !1, indexes: !1 },
                !1,
                function (S, N) {
                    return !x.isUndefined(N[S]);
                },
            )));
        const n = r.metaTokens,
            s = r.visitor || v,
            i = r.dots,
            o = r.indexes,
            a = r.Blob || (typeof Blob < "u" && Blob),
            l = r.maxDepth === void 0 ? lf : r.maxDepth,
            f = a && x.isSpecCompliantForm(e),
            u = [];
        if (!x.isFunction(s)) throw new TypeError("visitor must be a function");
        function d(y) {
            if (y === null) return "";
            if (x.isDate(y)) return y.toISOString();
            if (x.isBoolean(y)) return y.toString();
            if (!f && x.isBlob(y))
                throw new j("Blob is not supported. Use a Buffer instead.");
            if (x.isArrayBuffer(y) || x.isTypedArray(y)) {
                if (f && typeof a == "function") return new a([y]);
                if (typeof Buffer < "u") return Buffer.from(y);
                throw new j(
                    "Blob is not supported. Use a Buffer instead.",
                    j.ERR_NOT_SUPPORT,
                );
            }
            return y;
        }
        function h(y) {
            if (y > l)
                throw new j(
                    "Object is too deeply nested (" +
                        y +
                        " levels). Max depth: " +
                        l,
                    j.ERR_FORM_DATA_DEPTH_EXCEEDED,
                );
        }
        function g(y, S) {
            if (l === 1 / 0) return JSON.stringify(y);
            const N = [];
            return JSON.stringify(y, function (q, F) {
                if (!x.isObject(F)) return F;
                for (; N.length && N[N.length - 1] !== this; ) N.pop();
                return (N.push(F), h(S + N.length - 1), F);
            });
        }
        function v(y, S, N) {
            let P = y;
            if (x.isReactNative(e) && x.isReactNativeBlob(y))
                return (e.append(lo(N, S, i), d(y)), !1);
            if (y && !N && typeof y == "object") {
                if (x.endsWith(S, "{}"))
                    ((S = n ? S : S.slice(0, -2)), (y = g(y, 1)));
                else if (
                    (x.isArray(y) && Zh(y)) ||
                    ((x.isFileList(y) || x.endsWith(S, "[]")) &&
                        (P = x.toArray(y)))
                )
                    return (
                        (S = ff(S)),
                        P.forEach(function (F, te) {
                            !(x.isUndefined(F) || F === null) &&
                                e.append(
                                    o === !0
                                        ? lo([S], te, i)
                                        : o === null
                                          ? S
                                          : S + "[]",
                                    d(F),
                                );
                        }),
                        !1
                    );
            }
            return qo(y) ? !0 : (e.append(lo(N, S, i), d(y)), !1);
        }
        const b = Object.assign(Xh, {
            defaultVisitor: v,
            convertValue: d,
            isVisitable: qo,
        });
        function _(y, S, N = 0) {
            if (!x.isUndefined(y)) {
                if ((h(N), u.indexOf(y) !== -1))
                    throw new Error(
                        "Circular reference detected in " + S.join("."),
                    );
                (u.push(y),
                    x.forEach(y, function (q, F) {
                        (!(x.isUndefined(q) || q === null) &&
                            s.call(
                                e,
                                q,
                                x.isString(F) ? F.trim() : F,
                                S,
                                b,
                            )) === !0 && _(q, S ? S.concat(F) : [F], N + 1);
                    }),
                    u.pop());
            }
        }
        if (!x.isObject(t)) throw new TypeError("data must be an object");
        return (_(t), e);
    }
    function ml(t) {
        const e = {
            "!": "%21",
            "'": "%27",
            "(": "%28",
            ")": "%29",
            "~": "%7E",
            "%20": "+",
        };
        return encodeURIComponent(t).replace(/[!'()~]|%20/g, function (n) {
            return e[n];
        });
    }
    function ba(t, e) {
        ((this._pairs = []), t && si(t, this, e));
    }
    const cf = ba.prototype;
    cf.append = function (e, r) {
        this._pairs.push([e, r]);
    };
    cf.toString = function (e) {
        const r = e ? (n) => e.call(this, n, ml) : ml;
        return this._pairs
            .map(function (s) {
                return r(s[0]) + "=" + r(s[1]);
            }, "")
            .join("&");
    };
    function Jh(t) {
        return encodeURIComponent(t)
            .replace(/%3A/gi, ":")
            .replace(/%24/g, "$")
            .replace(/%2C/gi, ",")
            .replace(/%20/g, "+");
    }
    function uf(t, e, r) {
        if (!e) return t;
        t = t || "";
        const n = x.isFunction(r) ? { serialize: r } : r,
            s = x.getSafeProp(n, "encode") || Jh,
            i = x.getSafeProp(n, "serialize");
        let o;
        if (
            (i
                ? (o = i(e, n))
                : (o = x.isURLSearchParams(e)
                      ? e.toString()
                      : new ba(e, n).toString(s)),
            o)
        ) {
            const a = t.indexOf("#");
            (a !== -1 && (t = t.slice(0, a)),
                (t += (t.indexOf("?") === -1 ? "?" : "&") + o));
        }
        return t;
    }
    class bl {
        constructor() {
            this.handlers = [];
        }
        use(e, r, n) {
            return (
                this.handlers.push({
                    fulfilled: e,
                    rejected: r,
                    synchronous: n ? n.synchronous : !1,
                    runWhen: n ? n.runWhen : null,
                }),
                this.handlers.length - 1
            );
        }
        eject(e) {
            this.handlers[e] && (this.handlers[e] = null);
        }
        clear() {
            this.handlers && (this.handlers = []);
        }
        forEach(e) {
            x.forEach(this.handlers, function (n) {
                n !== null && e(n);
            });
        }
    }
    const ya = {
            silentJSONParsing: !0,
            forcedJSONParsing: !0,
            clarifyTimeoutError: !1,
            legacyInterceptorReqResOrdering: !0,
            advertiseZstdAcceptEncoding: !1,
            validateStatusUndefinedResolves: !0,
        },
        Yh = typeof URLSearchParams < "u" ? URLSearchParams : ba,
        Qh = typeof FormData < "u" ? FormData : null,
        ed = typeof Blob < "u" ? Blob : null,
        td = {
            isBrowser: !0,
            classes: { URLSearchParams: Yh, FormData: Qh, Blob: ed },
            protocols: ["http", "https", "file", "blob", "url", "data"],
        },
        va = typeof window < "u" && typeof document < "u",
        ko = (typeof navigator == "object" && navigator) || void 0,
        rd =
            va &&
            (!ko ||
                ["ReactNative", "NativeScript", "NS"].indexOf(ko.product) < 0),
        nd =
            typeof WorkerGlobalScope < "u" &&
            self instanceof WorkerGlobalScope &&
            typeof self.importScripts == "function",
        sd = (va && window.location.href) || "http://localhost",
        id = Object.freeze(
            Object.defineProperty(
                {
                    __proto__: null,
                    hasBrowserEnv: va,
                    hasStandardBrowserEnv: rd,
                    hasStandardBrowserWebWorkerEnv: nd,
                    navigator: ko,
                    origin: sd,
                },
                Symbol.toStringTag,
                { value: "Module" },
            ),
        ),
        Te = { ...id, ...td };
    function od(t, e) {
        return si(t, new Te.classes.URLSearchParams(), {
            visitor: function (r, n, s, i) {
                return Te.isNode && x.isBuffer(r)
                    ? (this.append(n, r.toString("base64")), !1)
                    : i.defaultVisitor.apply(this, arguments);
            },
            ...e,
        });
    }
    const yl = lf;
    function hf(t) {
        if (t > yl)
            throw new j(
                "FormData field is too deeply nested (" +
                    t +
                    " levels). Max depth: " +
                    yl,
                j.ERR_FORM_DATA_DEPTH_EXCEEDED,
            );
    }
    function ad(t) {
        const e = [],
            r = /\w+|\[(\w*)]/g;
        let n;
        for (; (n = r.exec(t)) !== null; )
            (hf(e.length), e.push(n[0] === "[]" ? "" : n[1] || n[0]));
        return e;
    }
    function ld(t) {
        const e = {},
            r = Object.keys(t);
        let n;
        const s = r.length;
        let i;
        for (n = 0; n < s; n++) ((i = r[n]), (e[i] = t[i]));
        return e;
    }
    function df(t) {
        function e(r, n, s, i) {
            hf(i);
            let o = r[i++];
            if (o === "__proto__") return !0;
            const a = Number.isFinite(+o),
                l = i >= r.length;
            return (
                (o = !o && x.isArray(s) ? s.length : o),
                l
                    ? (x.hasOwnProp(s, o)
                          ? (s[o] = x.isArray(s[o])
                                ? s[o].concat(n)
                                : [s[o], n])
                          : (s[o] = n),
                      !a)
                    : ((!x.hasOwnProp(s, o) || !x.isObject(s[o])) &&
                          (s[o] = []),
                      e(r, n, s[o], i) && x.isArray(s[o]) && (s[o] = ld(s[o])),
                      !a)
            );
        }
        if (x.isFormData(t) && x.isFunction(t.entries)) {
            const r = {};
            return (
                x.forEachEntry(t, (n, s) => {
                    e(ad(n), s, r, 0);
                }),
                r
            );
        }
        return null;
    }
    const Br = (t, e) => (t != null && x.hasOwnProp(t, e) ? t[e] : void 0);
    function fd(t, e, r) {
        if (x.isString(t))
            try {
                return ((e || JSON.parse)(t), x.trim(t));
            } catch (n) {
                if (n.name !== "SyntaxError") throw n;
            }
        return (r || JSON.stringify)(t);
    }
    const Jn = {
        transitional: ya,
        adapter: ["xhr", "http", "fetch"],
        transformRequest: [
            function (e, r) {
                const n = r.getContentType() || "",
                    s = n.indexOf("application/json") > -1,
                    i = x.isObject(e);
                if (
                    (i && x.isHTMLForm(e) && (e = new FormData(e)),
                    x.isFormData(e))
                )
                    return s ? JSON.stringify(df(e)) : e;
                if (
                    x.isArrayBuffer(e) ||
                    x.isBuffer(e) ||
                    x.isStream(e) ||
                    x.isFile(e) ||
                    x.isBlob(e) ||
                    x.isReadableStream(e)
                )
                    return e;
                if (x.isArrayBufferView(e)) return e.buffer;
                if (x.isURLSearchParams(e))
                    return (
                        r.setContentType(
                            "application/x-www-form-urlencoded;charset=utf-8",
                            !1,
                        ),
                        e.toString()
                    );
                let a;
                if (i) {
                    const l = Br(this, "formSerializer");
                    if (n.indexOf("application/x-www-form-urlencoded") > -1)
                        return od(e, l).toString();
                    if (
                        (a = x.isFileList(e)) ||
                        n.indexOf("multipart/form-data") > -1
                    ) {
                        const f = Br(this, "env"),
                            u = f && f.FormData;
                        return si(a ? { "files[]": e } : e, u && new u(), l);
                    }
                }
                return i || s
                    ? (r.setContentType("application/json", !1), fd(e))
                    : e;
            },
        ],
        transformResponse: [
            function (e) {
                const r = Br(this, "transitional") || Jn.transitional,
                    n = r && r.forcedJSONParsing,
                    s = Br(this, "responseType"),
                    i = s === "json";
                if (x.isResponse(e) || x.isReadableStream(e)) return e;
                if (e && x.isString(e) && ((n && !s) || i)) {
                    const a = !(r && r.silentJSONParsing) && i;
                    try {
                        return JSON.parse(e, Br(this, "parseReviver"));
                    } catch (l) {
                        if (a)
                            throw l.name === "SyntaxError"
                                ? j.from(
                                      l,
                                      j.ERR_BAD_RESPONSE,
                                      this,
                                      null,
                                      Br(this, "response"),
                                  )
                                : l;
                    }
                }
                return e;
            },
        ],
        timeout: 0,
        xsrfCookieName: "XSRF-TOKEN",
        xsrfHeaderName: "X-XSRF-TOKEN",
        maxContentLength: -1,
        maxBodyLength: -1,
        env: { FormData: Te.classes.FormData, Blob: Te.classes.Blob },
        validateStatus: function (e) {
            return e >= 200 && e < 300;
        },
        headers: {
            common: {
                Accept: "application/json, text/plain, */*",
                "Content-Type": void 0,
            },
        },
    };
    x.forEach(
        ["delete", "get", "head", "post", "put", "patch", "query"],
        (t) => {
            Jn.headers[t] = {};
        },
    );
    function fo(t, e) {
        const r = this || Jn,
            n = e || r,
            s = ke.from(n.headers);
        let i = n.data;
        return (
            x.forEach(t, function (a) {
                i = a.call(r, i, s.normalize(), e ? e.status : void 0);
            }),
            s.normalize(),
            i
        );
    }
    function pf(t) {
        return !!(t && t.__CANCEL__);
    }
    let Yn = class extends j {
        constructor(e, r, n) {
            (super(e ?? "canceled", j.ERR_CANCELED, r, n),
                (this.name = "CanceledError"),
                (this.__CANCEL__ = !0));
        }
    };
    function gf(t, e, r) {
        const n = r.config.validateStatus;
        !r.status || !n || n(r.status)
            ? t(r)
            : e(
                  new j(
                      "Request failed with status code " + r.status,
                      r.status >= 400 && r.status < 500
                          ? j.ERR_BAD_REQUEST
                          : j.ERR_BAD_RESPONSE,
                      r.config,
                      r.request,
                      r,
                  ),
              );
    }
    function cd(t) {
        const e = /^([-+\w]{1,25}):(?:\/\/)?/.exec(t);
        return (e && e[1]) || "";
    }
    function ud(t, e) {
        t = t || 10;
        const r = new Array(t),
            n = new Array(t);
        let s = 0,
            i = 0,
            o;
        return (
            (e = e !== void 0 ? e : 1e3),
            function (l) {
                const f = Date.now(),
                    u = n[i];
                (o || (o = f), (r[s] = l), (n[s] = f));
                let d = i,
                    h = 0;
                for (; d !== s; ) ((h += r[d++]), (d = d % t));
                if (
                    ((s = (s + 1) % t), s === i && (i = (i + 1) % t), f - o < e)
                )
                    return;
                const g = u && f - u;
                return g ? Math.round((h * 1e3) / g) : void 0;
            }
        );
    }
    function hd(t, e) {
        let r = 0,
            n = 1e3 / e,
            s,
            i;
        const o = (f, u = Date.now()) => {
            ((r = u), (s = null), i && (clearTimeout(i), (i = null)), t(...f));
        };
        return [
            (...f) => {
                const u = Date.now(),
                    d = u - r;
                d >= n
                    ? o(f, u)
                    : ((s = f),
                      i ||
                          (i = setTimeout(() => {
                              ((i = null), o(s));
                          }, n - d)));
            },
            () => s && o(s),
        ];
    }
    const Ks = (t, e, r = 3) => {
            let n = 0;
            const s = ud(50, 250);
            return hd((i) => {
                if (!i || typeof i.loaded != "number") return;
                const o = i.loaded,
                    a = i.lengthComputable ? i.total : void 0,
                    l = a != null ? Math.min(o, a) : o,
                    f = Math.max(0, l - n),
                    u = s(f);
                n = Math.max(n, l);
                const d = {
                    loaded: l,
                    total: a,
                    progress: a ? l / a : void 0,
                    bytes: f,
                    rate: u || void 0,
                    estimated: u && a ? (a - l) / u : void 0,
                    event: i,
                    lengthComputable: a != null,
                    [e ? "download" : "upload"]: !0,
                };
                t(d);
            }, r);
        },
        vl = (t, e) => {
            const r = t != null;
            return [
                (n) => e[0]({ lengthComputable: r, total: t, loaded: n }),
                e[1],
            ];
        },
        wl =
            (t) =>
            (...e) =>
                x.asap(() => t(...e)),
        dd = Te.hasStandardBrowserEnv
            ? ((t, e) => (r) => (
                  (r = new URL(r, Te.origin)),
                  t.protocol === r.protocol &&
                      t.host === r.host &&
                      (e || t.port === r.port)
              ))(
                  new URL(Te.origin),
                  Te.navigator &&
                      /(msie|trident)/i.test(Te.navigator.userAgent),
              )
            : () => !0,
        pd = Te.hasStandardBrowserEnv
            ? {
                  write(t, e, r, n, s, i, o) {
                      if (typeof document > "u") return;
                      const a = [`${t}=${encodeURIComponent(e)}`];
                      (x.isNumber(r) &&
                          a.push(`expires=${new Date(r).toUTCString()}`),
                          x.isString(n) && a.push(`path=${n}`),
                          x.isString(s) && a.push(`domain=${s}`),
                          i === !0 && a.push("secure"),
                          x.isString(o) && a.push(`SameSite=${o}`),
                          (document.cookie = a.join("; ")));
                  },
                  read(t) {
                      if (typeof document > "u") return null;
                      const e = document.cookie.split(";");
                      for (let r = 0; r < e.length; r++) {
                          const n = e[r].replace(/^\s+/, ""),
                              s = n.indexOf("=");
                          if (s !== -1 && n.slice(0, s) === t)
                              try {
                                  return decodeURIComponent(n.slice(s + 1));
                              } catch {
                                  return n.slice(s + 1);
                              }
                      }
                      return null;
                  },
                  remove(t) {
                      this.write(t, "", Date.now() - 864e5, "/");
                  },
              }
            : {
                  write() {},
                  read() {
                      return null;
                  },
                  remove() {},
              };
    function gd(t) {
        return typeof t != "string"
            ? !1
            : /^([a-z][a-z\d+\-.]*:)?\/\//i.test(t);
    }
    function md(t, e) {
        return e ? t.replace(/\/?\/$/, "") + "/" + e.replace(/^\/+/, "") : t;
    }
    const bd = /^https?:(?!\/\/)/i,
        yd = /[\t\n\r]/g;
    function vd(t) {
        let e = 0;
        for (; e < t.length && t.charCodeAt(e) <= 32; ) e++;
        return t.slice(e);
    }
    function wd(t) {
        return vd(t).replace(yd, "");
    }
    function El(t, e) {
        if (typeof t == "string" && bd.test(wd(t)))
            throw new j(
                'Invalid URL: missing "//" after protocol',
                j.ERR_INVALID_URL,
                e,
            );
    }
    function mf(t, e, r, n) {
        El(e, n);
        let s = !gd(e);
        return t && (s || r === !1) ? (El(t, n), md(t, e)) : e;
    }
    const _l = (t) => (t instanceof ke ? { ...t } : t);
    function hr(t, e) {
        ((t = t || {}), (e = e || {}));
        const r = Object.create(null);
        Object.defineProperty(r, "hasOwnProperty", {
            __proto__: null,
            value: Object.prototype.hasOwnProperty,
            enumerable: !1,
            writable: !0,
            configurable: !0,
        });
        function n(u, d, h, g) {
            return x.isPlainObject(u) && x.isPlainObject(d)
                ? x.merge.call({ caseless: g }, u, d)
                : x.isPlainObject(d)
                  ? x.merge({}, d)
                  : x.isArray(d)
                    ? d.slice()
                    : d;
        }
        function s(u, d, h, g) {
            if (x.isUndefined(d)) {
                if (!x.isUndefined(u)) return n(void 0, u, h, g);
            } else return n(u, d, h, g);
        }
        function i(u, d) {
            if (!x.isUndefined(d)) return n(void 0, d);
        }
        function o(u, d) {
            if (x.isUndefined(d)) {
                if (!x.isUndefined(u)) return n(void 0, u);
            } else return n(void 0, d);
        }
        function a(u) {
            const d = x.hasOwnProp(e, "transitional") ? e.transitional : void 0;
            if (!x.isUndefined(d))
                if (x.isPlainObject(d)) {
                    if (x.hasOwnProp(d, u)) return d[u];
                } else return;
            const h = x.hasOwnProp(t, "transitional") ? t.transitional : void 0;
            if (x.isPlainObject(h) && x.hasOwnProp(h, u)) return h[u];
        }
        function l(u, d, h) {
            if (x.hasOwnProp(e, h)) return n(u, d);
            if (x.hasOwnProp(t, h)) return n(void 0, u);
        }
        const f = {
            url: i,
            method: i,
            data: i,
            baseURL: o,
            transformRequest: o,
            transformResponse: o,
            paramsSerializer: o,
            timeout: o,
            timeoutMessage: o,
            withCredentials: o,
            withXSRFToken: o,
            adapter: o,
            responseType: o,
            xsrfCookieName: o,
            xsrfHeaderName: o,
            onUploadProgress: o,
            onDownloadProgress: o,
            decompress: o,
            maxContentLength: o,
            maxBodyLength: o,
            beforeRedirect: o,
            transport: o,
            httpAgent: o,
            httpsAgent: o,
            cancelToken: o,
            socketPath: o,
            allowedSocketPaths: o,
            responseEncoding: o,
            validateStatus: l,
            headers: (u, d, h) => s(_l(u), _l(d), h, !0),
        };
        return (
            x.forEach(Object.keys({ ...t, ...e }), function (d) {
                if (
                    d === "__proto__" ||
                    d === "constructor" ||
                    d === "prototype"
                )
                    return;
                const h = x.hasOwnProp(f, d) ? f[d] : s,
                    g = x.hasOwnProp(t, d) ? t[d] : void 0,
                    v = x.hasOwnProp(e, d) ? e[d] : void 0,
                    b = h(g, v, d);
                (x.isUndefined(b) && h !== l) || (r[d] = b);
            }),
            x.hasOwnProp(e, "validateStatus") &&
                x.isUndefined(e.validateStatus) &&
                a("validateStatusUndefinedResolves") === !1 &&
                (x.hasOwnProp(t, "validateStatus")
                    ? (r.validateStatus = n(void 0, t.validateStatus))
                    : delete r.validateStatus),
            r
        );
    }
    const Ed = ["content-type", "content-length"];
    function _d(t, e, r) {
        if (r !== "content-only") {
            t.set(e);
            return;
        }
        Object.entries(e || {}).forEach(([n, s]) => {
            Ed.includes(n.toLowerCase()) && t.set(n, s);
        });
    }
    const xd = (t) =>
        encodeURIComponent(t).replace(/%([0-9A-F]{2})/gi, (e, r) =>
            String.fromCharCode(parseInt(r, 16)),
        );
    function bf(t) {
        const e = hr({}, t),
            r = (h) => (x.hasOwnProp(e, h) ? e[h] : void 0),
            n = r("data");
        let s = r("withXSRFToken");
        const i = r("xsrfHeaderName"),
            o = r("xsrfCookieName");
        let a = r("headers");
        const l = r("auth"),
            f = r("baseURL"),
            u = r("allowAbsoluteUrls"),
            d = r("url");
        if (
            ((e.headers = a = ke.from(a)),
            (e.url = uf(mf(f, d, u, e), r("params"), r("paramsSerializer"))),
            l)
        ) {
            const h = x.getSafeProp(l, "username") || "",
                g = x.getSafeProp(l, "password") || "";
            try {
                a.set(
                    "Authorization",
                    "Basic " + btoa(h + ":" + (g ? xd(g) : "")),
                );
            } catch (v) {
                throw j.from(v, j.ERR_BAD_OPTION_VALUE, t);
            }
        }
        if (
            (x.isFormData(n) &&
                (Te.hasStandardBrowserEnv ||
                Te.hasStandardBrowserWebWorkerEnv ||
                x.isReactNative(n)
                    ? a.setContentType(void 0)
                    : x.isFunction(n.getHeaders) &&
                      _d(a, n.getHeaders(), r("formDataHeaderPolicy"))),
            Te.hasStandardBrowserEnv &&
                (x.isFunction(s) && (s = s(e)),
                s === !0 || (s == null && dd(e.url))))
        ) {
            const g = i && o && pd.read(o);
            g && a.set(i, g);
        }
        return e;
    }
    const Ad = typeof XMLHttpRequest < "u",
        Sd =
            Ad &&
            function (t) {
                return new Promise(function (r, n) {
                    const s = bf(t);
                    let i = s.data;
                    const o = ke.from(s.headers).normalize();
                    let {
                            responseType: a,
                            onUploadProgress: l,
                            onDownloadProgress: f,
                        } = s,
                        u,
                        d,
                        h,
                        g,
                        v;
                    function b() {
                        (g && g(),
                            v && v(),
                            s.cancelToken && s.cancelToken.unsubscribe(u),
                            s.signal &&
                                s.signal.removeEventListener("abort", u));
                    }
                    let _ = new XMLHttpRequest();
                    (_.open(s.method.toUpperCase(), s.url, !0),
                        (_.timeout = s.timeout));
                    function y() {
                        if (!_) return;
                        const N = ke.from(
                                "getAllResponseHeaders" in _ &&
                                    _.getAllResponseHeaders(),
                            ),
                            q = {
                                data:
                                    !a || a === "text" || a === "json"
                                        ? _.responseText
                                        : _.response,
                                status: _.status,
                                statusText: _.statusText,
                                headers: N,
                                config: t,
                                request: _,
                            };
                        (gf(
                            function (te) {
                                (r(te), b());
                            },
                            function (te) {
                                (n(te), b());
                            },
                            q,
                        ),
                            (_ = null));
                    }
                    ("onloadend" in _
                        ? (_.onloadend = y)
                        : (_.onreadystatechange = function () {
                              !_ ||
                                  _.readyState !== 4 ||
                                  (_.status === 0 &&
                                      !(
                                          _.responseURL &&
                                          _.responseURL.startsWith("file:")
                                      )) ||
                                  setTimeout(y);
                          }),
                        (_.onabort = function () {
                            _ &&
                                (n(
                                    new j(
                                        "Request aborted",
                                        j.ECONNABORTED,
                                        t,
                                        _,
                                    ),
                                ),
                                b(),
                                (_ = null));
                        }),
                        (_.onerror = function (P) {
                            const q =
                                    P && P.message
                                        ? P.message
                                        : "Network Error",
                                F = new j(q, j.ERR_NETWORK, t, _);
                            ((F.event = P || null), n(F), b(), (_ = null));
                        }),
                        (_.ontimeout = function () {
                            let P = s.timeout
                                ? "timeout of " + s.timeout + "ms exceeded"
                                : "timeout exceeded";
                            const q = s.transitional || ya;
                            (s.timeoutErrorMessage &&
                                (P = s.timeoutErrorMessage),
                                n(
                                    new j(
                                        P,
                                        q.clarifyTimeoutError
                                            ? j.ETIMEDOUT
                                            : j.ECONNABORTED,
                                        t,
                                        _,
                                    ),
                                ),
                                b(),
                                (_ = null));
                        }),
                        i === void 0 && o.setContentType(null),
                        "setRequestHeader" in _ &&
                            x.forEach(of(o), function (P, q) {
                                _.setRequestHeader(q, P);
                            }),
                        x.isUndefined(s.withCredentials) ||
                            (_.withCredentials = !!s.withCredentials),
                        a && a !== "json" && (_.responseType = s.responseType),
                        f &&
                            (([h, v] = Ks(f, !0)),
                            _.addEventListener("progress", h)),
                        l &&
                            _.upload &&
                            (([d, g] = Ks(l)),
                            _.upload.addEventListener("progress", d),
                            _.upload.addEventListener("loadend", g)),
                        (s.cancelToken || s.signal) &&
                            ((u = (N) => {
                                _ &&
                                    (n(!N || N.type ? new Yn(null, t, _) : N),
                                    _.abort(),
                                    b(),
                                    (_ = null));
                            }),
                            s.cancelToken && s.cancelToken.subscribe(u),
                            s.signal &&
                                (s.signal.aborted
                                    ? u()
                                    : s.signal.addEventListener("abort", u))));
                    const S = cd(s.url);
                    if (S && !Te.protocols.includes(S)) {
                        (n(
                            new j(
                                "Unsupported protocol " + S + ":",
                                j.ERR_BAD_REQUEST,
                                t,
                            ),
                        ),
                            b());
                        return;
                    }
                    _.send(i || null);
                });
            },
        Td = (t, e) => {
            if (((t = t ? t.filter(Boolean) : []), !e && !t.length)) return;
            const r = new AbortController();
            let n = !1;
            const s = function (l) {
                if (!n) {
                    ((n = !0), o());
                    const f = l instanceof Error ? l : this.reason;
                    r.abort(
                        f instanceof j
                            ? f
                            : new Yn(f instanceof Error ? f.message : f),
                    );
                }
            };
            let i =
                e &&
                setTimeout(() => {
                    ((i = null),
                        s(new j(`timeout of ${e}ms exceeded`, j.ETIMEDOUT)));
                }, e);
            const o = () => {
                t &&
                    (i && clearTimeout(i),
                    (i = null),
                    t.forEach((l) => {
                        l.unsubscribe
                            ? l.unsubscribe(s)
                            : l.removeEventListener("abort", s);
                    }),
                    (t = null));
            };
            t.forEach((l) => l.addEventListener("abort", s, { once: !0 }));
            const { signal: a } = r;
            return ((a.unsubscribe = () => x.asap(o)), a);
        },
        Nd = function* (t, e) {
            let r = t.byteLength;
            if (r < e) {
                yield t;
                return;
            }
            let n = 0,
                s;
            for (; n < r; ) ((s = n + e), yield t.slice(n, s), (n = s));
        },
        Od = async function* (t, e) {
            for await (const r of Cd(t)) yield* Nd(r, e);
        },
        Cd = async function* (t) {
            if (t[Symbol.asyncIterator]) {
                yield* t;
                return;
            }
            const e = t.getReader();
            try {
                for (;;) {
                    const { done: r, value: n } = await e.read();
                    if (r) break;
                    yield n;
                }
            } finally {
                await e.cancel();
            }
        },
        xl = (t, e, r, n) => {
            const s = Od(t, e);
            let i = 0,
                o,
                a = (l) => {
                    o || ((o = !0), n && n(l));
                };
            return new ReadableStream(
                {
                    async pull(l) {
                        try {
                            const { done: f, value: u } = await s.next();
                            if (f) {
                                (a(), l.close());
                                return;
                            }
                            let d = u.byteLength;
                            if (r) {
                                let h = (i += d);
                                r(h);
                            }
                            l.enqueue(new Uint8Array(u));
                        } catch (f) {
                            throw (a(f), f);
                        }
                    },
                    cancel(l) {
                        return (a(l), s.return());
                    },
                },
                { highWaterMark: 2 },
            );
        },
        Ws = (t) =>
            (t >= 48 && t <= 57) ||
            (t >= 65 && t <= 70) ||
            (t >= 97 && t <= 102),
        Ld = (t, e, r) =>
            e + 2 < r && Ws(t.charCodeAt(e + 1)) && Ws(t.charCodeAt(e + 2));
    function Rd(t) {
        if (!t || typeof t != "string" || !t.startsWith("data:")) return 0;
        const e = t.indexOf(",");
        if (e < 0) return 0;
        const r = t.slice(5, e),
            n = t.slice(e + 1);
        if (/;base64/i.test(r)) {
            let o = n.length;
            const a = n.length;
            for (let g = 0; g < a; g++)
                if (n.charCodeAt(g) === 37 && g + 2 < a) {
                    const v = n.charCodeAt(g + 1),
                        b = n.charCodeAt(g + 2);
                    Ws(v) && Ws(b) && ((o -= 2), (g += 2));
                }
            let l = 0,
                f = a - 1;
            const u = (g) =>
                g >= 2 &&
                n.charCodeAt(g - 2) === 37 &&
                n.charCodeAt(g - 1) === 51 &&
                (n.charCodeAt(g) === 68 || n.charCodeAt(g) === 100);
            (f >= 0 &&
                (n.charCodeAt(f) === 61 ? (l++, f--) : u(f) && (l++, (f -= 3))),
                l === 1 && f >= 0 && (n.charCodeAt(f) === 61 || u(f)) && l++);
            const h = Math.floor(o / 4) * 3 - (l || 0);
            return h > 0 ? h : 0;
        }
        let i = 0;
        for (let o = 0, a = n.length; o < a; o++) {
            const l = n.charCodeAt(o);
            if (l === 37 && Ld(n, o, a)) ((i += 1), (o += 2));
            else if (l < 128) i += 1;
            else if (l < 2048) i += 2;
            else if (l >= 55296 && l <= 56319 && o + 1 < a) {
                const f = n.charCodeAt(o + 1);
                f >= 56320 && f <= 57343 ? ((i += 4), o++) : (i += 3);
            } else i += 3;
        }
        return i;
    }
    const wa = "1.18.1",
        Al = 64 * 1024,
        { isFunction: _s } = x,
        qd = (t) =>
            encodeURIComponent(t).replace(/%([0-9A-F]{2})/gi, (e, r) =>
                String.fromCharCode(parseInt(r, 16)),
            ),
        Sl = (t) => {
            if (!x.isString(t)) return t;
            try {
                return decodeURIComponent(t);
            } catch {
                return t;
            }
        },
        Tl = (t, ...e) => {
            try {
                return !!t(...e);
            } catch {
                return !1;
            }
        },
        kd = (t) => {
            const e = t.indexOf("://");
            let r = t;
            return (
                e !== -1 && (r = r.slice(e + 3)),
                r.includes("@") || r.includes(":")
            );
        },
        Id = (t) => {
            const e =
                    x.global !== void 0 && x.global !== null
                        ? x.global
                        : globalThis,
                { ReadableStream: r, TextEncoder: n } = e;
            t = x.merge.call(
                { skipUndefined: !0 },
                { Request: e.Request, Response: e.Response },
                t,
            );
            const { fetch: s, Request: i, Response: o } = t,
                a = s ? _s(s) : typeof fetch == "function",
                l = _s(i),
                f = _s(o);
            if (!a) return !1;
            const u = a && _s(r),
                d =
                    a &&
                    (typeof n == "function"
                        ? (
                              (y) => (S) =>
                                  y.encode(S)
                          )(new n())
                        : async (y) =>
                              new Uint8Array(await new i(y).arrayBuffer())),
                h =
                    l &&
                    u &&
                    Tl(() => {
                        let y = !1;
                        const S = new i(Te.origin, {
                                body: new r(),
                                method: "POST",
                                get duplex() {
                                    return ((y = !0), "half");
                                },
                            }),
                            N = S.headers.has("Content-Type");
                        return (S.body != null && S.body.cancel(), y && !N);
                    }),
                g = f && u && Tl(() => x.isReadableStream(new o("").body)),
                v = { stream: g && ((y) => y.body) };
            a &&
                ["text", "arrayBuffer", "blob", "formData", "stream"].forEach(
                    (y) => {
                        !v[y] &&
                            (v[y] = (S, N) => {
                                let P = S && S[y];
                                if (P) return P.call(S);
                                throw new j(
                                    `Response type '${y}' is not supported`,
                                    j.ERR_NOT_SUPPORT,
                                    N,
                                );
                            });
                    },
                );
            const b = async (y) => {
                    if (y == null) return 0;
                    if (x.isBlob(y)) return y.size;
                    if (x.isSpecCompliantForm(y))
                        return (
                            await new i(Te.origin, {
                                method: "POST",
                                body: y,
                            }).arrayBuffer()
                        ).byteLength;
                    if (x.isArrayBufferView(y) || x.isArrayBuffer(y))
                        return y.byteLength;
                    if ((x.isURLSearchParams(y) && (y = y + ""), x.isString(y)))
                        return (await d(y)).byteLength;
                },
                _ = async (y, S) => {
                    const N = x.toFiniteNumber(y.getContentLength());
                    return N ?? b(S);
                };
            return async (y) => {
                let {
                    url: S,
                    method: N,
                    data: P,
                    signal: q,
                    cancelToken: F,
                    timeout: te,
                    onDownloadProgress: re,
                    onUploadProgress: de,
                    responseType: ce,
                    headers: w,
                    withCredentials: R = "same-origin",
                    fetchOptions: T,
                    maxContentLength: k,
                    maxBodyLength: M,
                } = bf(y);
                const m = x.isNumber(k) && k > -1,
                    A = x.isNumber(M) && M > -1,
                    O = (W) => (x.hasOwnProp(y, W) ? y[W] : void 0);
                let B = s || fetch;
                ce = ce ? (ce + "").toLowerCase() : "text";
                let I = Td([q, F && F.toAbortSignal()], te),
                    $ = null;
                const D =
                    I &&
                    I.unsubscribe &&
                    (() => {
                        I.unsubscribe();
                    });
                let V,
                    Z = null;
                const U = () =>
                    new j(
                        "Request body larger than maxBodyLength limit",
                        j.ERR_BAD_REQUEST,
                        y,
                        $,
                    );
                try {
                    let W;
                    const Y = O("auth");
                    if (Y) {
                        const J = x.getSafeProp(Y, "username") || "",
                            xe = x.getSafeProp(Y, "password") || "";
                        W = { username: J, password: xe };
                    }
                    if (kd(S)) {
                        const J = new URL(S, Te.origin);
                        if (!W && (J.username || J.password)) {
                            const xe = Sl(J.username),
                                ze = Sl(J.password);
                            W = { username: xe, password: ze };
                        }
                        (J.username || J.password) &&
                            ((J.username = ""),
                            (J.password = ""),
                            (S = J.href));
                    }
                    if (
                        (W &&
                            (w.delete("authorization"),
                            w.set(
                                "Authorization",
                                "Basic " +
                                    btoa(
                                        qd(
                                            (W.username || "") +
                                                ":" +
                                                (W.password || ""),
                                        ),
                                    ),
                            )),
                        m &&
                            typeof S == "string" &&
                            S.startsWith("data:") &&
                            Rd(S) > k)
                    )
                        throw new j(
                            "maxContentLength size of " + k + " exceeded",
                            j.ERR_BAD_RESPONSE,
                            y,
                            $,
                        );
                    if (A && N !== "get" && N !== "head") {
                        const J = await b(P);
                        if (
                            typeof J == "number" &&
                            isFinite(J) &&
                            ((V = J), J > M)
                        )
                            throw U();
                    }
                    const X = A && (x.isReadableStream(P) || x.isStream(P)),
                        ne = (J, xe, ze) =>
                            xl(
                                J,
                                Al,
                                (Xe) => {
                                    if (A && Xe > M) throw (Z = U());
                                    xe && xe(Xe);
                                },
                                ze,
                            );
                    if (h && N !== "get" && N !== "head" && (de || X)) {
                        if (((V = V ?? (await _(w, P))), V !== 0 || X)) {
                            let J = new i(S, {
                                    method: "POST",
                                    body: P,
                                    duplex: "half",
                                }),
                                xe;
                            if (
                                (x.isFormData(P) &&
                                    (xe = J.headers.get("content-type")) &&
                                    w.setContentType(xe),
                                J.body)
                            ) {
                                const [ze, Xe] =
                                    (de && vl(V, Ks(wl(de)))) || [];
                                P = ne(J.body, ze, Xe);
                            }
                        }
                    } else if (X && !l && u && N !== "get" && N !== "head")
                        P = ne(P);
                    else if (X && l && !h && N !== "get" && N !== "head")
                        throw new j(
                            "Stream request bodies are not supported by the current fetch implementation",
                            j.ERR_NOT_SUPPORT,
                            y,
                            $,
                        );
                    x.isString(R) || (R = R ? "include" : "omit");
                    const se = l && "credentials" in i.prototype;
                    if (x.isFormData(P)) {
                        const J = w.getContentType();
                        J &&
                            /^multipart\/form-data/i.test(J) &&
                            !/boundary=/i.test(J) &&
                            w.delete("content-type");
                    }
                    w.set("User-Agent", "axios/" + wa, !1);
                    const ie = {
                        ...T,
                        signal: I,
                        method: N.toUpperCase(),
                        headers: of(w.normalize()),
                        body: P,
                        duplex: "half",
                        credentials: se ? R : void 0,
                    };
                    $ = l && new i(S, ie);
                    let ee = await (l ? B($, T) : B(S, ie));
                    const le = ke.from(ee.headers);
                    if (m) {
                        const J = x.toFiniteNumber(le.getContentLength());
                        if (J != null && J > k)
                            throw new j(
                                "maxContentLength size of " + k + " exceeded",
                                j.ERR_BAD_RESPONSE,
                                y,
                                $,
                            );
                    }
                    const me = g && (ce === "stream" || ce === "response");
                    if (g && ee.body && (re || m || (me && D))) {
                        const J = {};
                        ["status", "statusText", "headers"].forEach((ct) => {
                            J[ct] = ee[ct];
                        });
                        const xe = x.toFiniteNumber(le.getContentLength()),
                            [ze, Xe] = (re && vl(xe, Ks(wl(re), !0))) || [];
                        let Et = 0;
                        const Gt = (ct) => {
                            if (m && ((Et = ct), Et > k))
                                throw new j(
                                    "maxContentLength size of " +
                                        k +
                                        " exceeded",
                                    j.ERR_BAD_RESPONSE,
                                    y,
                                    $,
                                );
                            ze && ze(ct);
                        };
                        ee = new o(
                            xl(ee.body, Al, Gt, () => {
                                (Xe && Xe(), D && D());
                            }),
                            J,
                        );
                    }
                    ce = ce || "text";
                    let ue = await v[x.findKey(v, ce) || "text"](ee, y);
                    if (m && !g && !me) {
                        let J;
                        if (
                            (ue != null &&
                                (typeof ue.byteLength == "number"
                                    ? (J = ue.byteLength)
                                    : typeof ue.size == "number"
                                      ? (J = ue.size)
                                      : typeof ue == "string" &&
                                        (J =
                                            typeof n == "function"
                                                ? new n().encode(ue).byteLength
                                                : ue.length)),
                            typeof J == "number" && J > k)
                        )
                            throw new j(
                                "maxContentLength size of " + k + " exceeded",
                                j.ERR_BAD_RESPONSE,
                                y,
                                $,
                            );
                    }
                    return (
                        !me && D && D(),
                        await new Promise((J, xe) => {
                            gf(J, xe, {
                                data: ue,
                                headers: ke.from(ee.headers),
                                status: ee.status,
                                statusText: ee.statusText,
                                config: y,
                                request: $,
                            });
                        })
                    );
                } catch (W) {
                    if ((D && D(), I && I.aborted && I.reason instanceof j)) {
                        const Y = I.reason;
                        throw (
                            (Y.config = y),
                            $ && (Y.request = $),
                            W !== Y &&
                                Object.defineProperty(Y, "cause", {
                                    __proto__: null,
                                    value: W,
                                    writable: !0,
                                    enumerable: !1,
                                    configurable: !0,
                                }),
                            Y
                        );
                    }
                    if (Z) throw ($ && !Z.request && (Z.request = $), Z);
                    if (W instanceof j)
                        throw ($ && !W.request && (W.request = $), W);
                    if (
                        W &&
                        W.name === "TypeError" &&
                        /Load failed|fetch/i.test(W.message)
                    ) {
                        const Y = new j(
                            "Network Error",
                            j.ERR_NETWORK,
                            y,
                            $,
                            W && W.response,
                        );
                        throw (
                            Object.defineProperty(Y, "cause", {
                                __proto__: null,
                                value: W.cause || W,
                                writable: !0,
                                enumerable: !1,
                                configurable: !0,
                            }),
                            Y
                        );
                    }
                    throw j.from(W, W && W.code, y, $, W && W.response);
                }
            };
        },
        Pd = new Map(),
        yf = (t) => {
            let e = (t && t.env) || {};
            const { fetch: r, Request: n, Response: s } = e,
                i = [n, s, r];
            let o = i.length,
                a = o,
                l,
                f,
                u = Pd;
            for (; a--; )
                ((l = i[a]),
                    (f = u.get(l)),
                    f === void 0 && u.set(l, (f = a ? new Map() : Id(e))),
                    (u = f));
            return f;
        };
    yf();
    const Ea = { http: Gh, xhr: Sd, fetch: { get: yf } };
    x.forEach(Ea, (t, e) => {
        if (t) {
            try {
                Object.defineProperty(t, "name", { __proto__: null, value: e });
            } catch {}
            Object.defineProperty(t, "adapterName", {
                __proto__: null,
                value: e,
            });
        }
    });
    const Nl = (t) => `- ${t}`,
        Md = (t) => x.isFunction(t) || t === null || t === !1;
    function Bd(t, e) {
        t = x.isArray(t) ? t : [t];
        const { length: r } = t;
        let n, s;
        const i = {};
        for (let o = 0; o < r; o++) {
            n = t[o];
            let a;
            if (
                ((s = n),
                !Md(n) &&
                    ((s = Ea[(a = String(n)).toLowerCase()]), s === void 0))
            )
                throw new j(`Unknown adapter '${a}'`);
            if (s && (x.isFunction(s) || (s = s.get(e)))) break;
            i[a || "#" + o] = s;
        }
        if (!s) {
            const o = Object.entries(i).map(
                ([l, f]) =>
                    `adapter ${l} ` +
                    (f === !1
                        ? "is not supported by the environment"
                        : "is not available in the build"),
            );
            let a = r
                ? o.length > 1
                    ? `since :
` +
                      o.map(Nl).join(`
`)
                    : " " + Nl(o[0])
                : "as no adapter specified";
            throw new j(
                "There is no suitable adapter to dispatch the request " + a,
                j.ERR_NOT_SUPPORT,
            );
        }
        return s;
    }
    const vf = { getAdapter: Bd, adapters: Ea };
    function co(t) {
        if (
            (t.cancelToken && t.cancelToken.throwIfRequested(),
            t.signal && t.signal.aborted)
        )
            throw new Yn(null, t);
    }
    function Ol(t) {
        return (
            co(t),
            (t.headers = ke.from(t.headers)),
            (t.data = fo.call(t, t.transformRequest)),
            ["post", "put", "patch"].indexOf(t.method) !== -1 &&
                t.headers.setContentType(
                    "application/x-www-form-urlencoded",
                    !1,
                ),
            vf
                .getAdapter(
                    t.adapter || Jn.adapter,
                    t,
                )(t)
                .then(
                    function (n) {
                        (co(t), (t.response = n));
                        try {
                            n.data = fo.call(t, t.transformResponse, n);
                        } finally {
                            delete t.response;
                        }
                        return ((n.headers = ke.from(n.headers)), n);
                    },
                    function (n) {
                        if (!pf(n) && (co(t), n && n.response)) {
                            t.response = n.response;
                            try {
                                n.response.data = fo.call(
                                    t,
                                    t.transformResponse,
                                    n.response,
                                );
                            } finally {
                                delete t.response;
                            }
                            n.response.headers = ke.from(n.response.headers);
                        }
                        return Promise.reject(n);
                    },
                )
        );
    }
    const ii = {};
    ["object", "boolean", "number", "function", "string", "symbol"].forEach(
        (t, e) => {
            ii[t] = function (n) {
                return typeof n === t || "a" + (e < 1 ? "n " : " ") + t;
            };
        },
    );
    const Cl = {};
    ii.transitional = function (e, r, n) {
        function s(i, o) {
            return (
                "[Axios v" +
                wa +
                "] Transitional option '" +
                i +
                "'" +
                o +
                (n ? ". " + n : "")
            );
        }
        return (i, o, a) => {
            if (e === !1)
                throw new j(
                    s(o, " has been removed" + (r ? " in " + r : "")),
                    j.ERR_DEPRECATED,
                );
            return (
                r &&
                    !Cl[o] &&
                    ((Cl[o] = !0),
                    console.warn(
                        s(
                            o,
                            " has been deprecated since v" +
                                r +
                                " and will be removed in the near future",
                        ),
                    )),
                e ? e(i, o, a) : !0
            );
        };
    };
    ii.spelling = function (e) {
        return (r, n) => (
            console.warn(`${n} is likely a misspelling of ${e}`),
            !0
        );
    };
    function $d(t, e, r) {
        if (typeof t != "object" || t === null)
            throw new j("options must be an object", j.ERR_BAD_OPTION_VALUE);
        const n = Object.keys(t);
        let s = n.length;
        for (; s-- > 0; ) {
            const i = n[s],
                o = Object.prototype.hasOwnProperty.call(e, i) ? e[i] : void 0;
            if (o) {
                const a = t[i],
                    l = a === void 0 || o(a, i, t);
                if (l !== !0)
                    throw new j(
                        "option " + i + " must be " + l,
                        j.ERR_BAD_OPTION_VALUE,
                    );
                continue;
            }
            if (r !== !0) throw new j("Unknown option " + i, j.ERR_BAD_OPTION);
        }
    }
    const Us = { assertOptions: $d, validators: ii },
        Re = Us.validators;
    let or = class {
        constructor(e) {
            ((this.defaults = e || {}),
                (this.interceptors = {
                    request: new bl(),
                    response: new bl(),
                }));
        }
        async request(e, r) {
            try {
                return await this._request(e, r);
            } catch (n) {
                if (n instanceof Error) {
                    let s = {};
                    Error.captureStackTrace
                        ? Error.captureStackTrace(s)
                        : (s = new Error());
                    const i = (() => {
                        if (!s.stack) return "";
                        const o = s.stack.indexOf(`
`);
                        return o === -1 ? "" : s.stack.slice(o + 1);
                    })();
                    try {
                        if (!n.stack) n.stack = i;
                        else if (i) {
                            const o = i.indexOf(`
`),
                                a =
                                    o === -1
                                        ? -1
                                        : i.indexOf(
                                              `
`,
                                              o + 1,
                                          ),
                                l = a === -1 ? "" : i.slice(a + 1);
                            String(n.stack).endsWith(l) ||
                                (n.stack +=
                                    `
` + i);
                        }
                    } catch {}
                }
                throw n;
            }
        }
        _request(e, r) {
            (typeof e == "string"
                ? ((r = r || {}), (r.url = e))
                : (r = e || {}),
                (r = hr(this.defaults, r)));
            const { transitional: n, paramsSerializer: s, headers: i } = r;
            (n !== void 0 &&
                Us.assertOptions(
                    n,
                    {
                        silentJSONParsing: Re.transitional(Re.boolean),
                        forcedJSONParsing: Re.transitional(Re.boolean),
                        clarifyTimeoutError: Re.transitional(Re.boolean),
                        legacyInterceptorReqResOrdering: Re.transitional(
                            Re.boolean,
                        ),
                        advertiseZstdAcceptEncoding: Re.transitional(
                            Re.boolean,
                        ),
                        validateStatusUndefinedResolves: Re.transitional(
                            Re.boolean,
                        ),
                    },
                    !1,
                ),
                s != null &&
                    (x.isFunction(s)
                        ? (r.paramsSerializer = { serialize: s })
                        : Us.assertOptions(
                              s,
                              { encode: Re.function, serialize: Re.function },
                              !0,
                          )),
                r.allowAbsoluteUrls !== void 0 ||
                    (this.defaults.allowAbsoluteUrls !== void 0
                        ? (r.allowAbsoluteUrls =
                              this.defaults.allowAbsoluteUrls)
                        : (r.allowAbsoluteUrls = !0)),
                Us.assertOptions(
                    r,
                    {
                        baseUrl: Re.spelling("baseURL"),
                        withXsrfToken: Re.spelling("withXSRFToken"),
                    },
                    !0,
                ),
                (r.method = (
                    r.method ||
                    this.defaults.method ||
                    "get"
                ).toLowerCase()));
            let o = i && x.merge(i.common, i[r.method]);
            (i &&
                x.forEach(
                    [
                        "delete",
                        "get",
                        "head",
                        "post",
                        "put",
                        "patch",
                        "query",
                        "common",
                    ],
                    (v) => {
                        delete i[v];
                    },
                ),
                (r.headers = ke.concat(o, i)));
            const a = [];
            let l = !0;
            this.interceptors.request.forEach(function (b) {
                if (typeof b.runWhen == "function" && b.runWhen(r) === !1)
                    return;
                l = l && b.synchronous;
                const _ = r.transitional || ya;
                _ && _.legacyInterceptorReqResOrdering
                    ? a.unshift(b.fulfilled, b.rejected)
                    : a.push(b.fulfilled, b.rejected);
            });
            const f = [];
            this.interceptors.response.forEach(function (b) {
                f.push(b.fulfilled, b.rejected);
            });
            let u,
                d = 0,
                h;
            if (!l) {
                const v = [Ol.bind(this), void 0];
                for (
                    v.unshift(...a),
                        v.push(...f),
                        h = v.length,
                        u = Promise.resolve(r);
                    d < h;
                )
                    u = u.then(v[d++], v[d++]);
                return u;
            }
            h = a.length;
            let g = r;
            for (; d < h; ) {
                const v = a[d++],
                    b = a[d++];
                try {
                    g = v(g);
                } catch (_) {
                    b.call(this, _);
                    break;
                }
            }
            try {
                u = Ol.call(this, g);
            } catch (v) {
                return Promise.reject(v);
            }
            for (d = 0, h = f.length; d < h; ) u = u.then(f[d++], f[d++]);
            return u;
        }
        getUri(e) {
            e = hr(this.defaults, e);
            const r = mf(e.baseURL, e.url, e.allowAbsoluteUrls, e);
            return uf(r, e.params, e.paramsSerializer);
        }
    };
    x.forEach(["delete", "get", "head", "options"], function (e) {
        or.prototype[e] = function (r, n) {
            return this.request(
                hr(n || {}, {
                    method: e,
                    url: r,
                    data: n && x.hasOwnProp(n, "data") ? n.data : void 0,
                }),
            );
        };
    });
    x.forEach(["post", "put", "patch", "query"], function (e) {
        function r(n) {
            return function (i, o, a) {
                return this.request(
                    hr(a || {}, {
                        method: e,
                        headers: n
                            ? { "Content-Type": "multipart/form-data" }
                            : {},
                        url: i,
                        data: o,
                    }),
                );
            };
        }
        ((or.prototype[e] = r()),
            e !== "query" && (or.prototype[e + "Form"] = r(!0)));
    });
    let Dd = class wf {
        constructor(e) {
            if (typeof e != "function")
                throw new TypeError("executor must be a function.");
            let r;
            this.promise = new Promise(function (i) {
                r = i;
            });
            const n = this;
            (this.promise.then((s) => {
                if (!n._listeners) return;
                let i = n._listeners.length;
                for (; i-- > 0; ) n._listeners[i](s);
                n._listeners = null;
            }),
                (this.promise.then = (s) => {
                    let i;
                    const o = new Promise((a) => {
                        (n.subscribe(a), (i = a));
                    }).then(s);
                    return (
                        (o.cancel = function () {
                            n.unsubscribe(i);
                        }),
                        o
                    );
                }),
                e(function (i, o, a) {
                    n.reason || ((n.reason = new Yn(i, o, a)), r(n.reason));
                }));
        }
        throwIfRequested() {
            if (this.reason) throw this.reason;
        }
        subscribe(e) {
            if (this.reason) {
                e(this.reason);
                return;
            }
            this._listeners ? this._listeners.push(e) : (this._listeners = [e]);
        }
        unsubscribe(e) {
            if (!this._listeners) return;
            const r = this._listeners.indexOf(e);
            r !== -1 && this._listeners.splice(r, 1);
        }
        toAbortSignal() {
            const e = new AbortController(),
                r = (n) => {
                    e.abort(n);
                };
            return (
                this.subscribe(r),
                (e.signal.unsubscribe = () => this.unsubscribe(r)),
                e.signal
            );
        }
        static source() {
            let e;
            return {
                token: new wf(function (s) {
                    e = s;
                }),
                cancel: e,
            };
        }
    };
    function jd(t) {
        return function (r) {
            return t.apply(null, r);
        };
    }
    function Ud(t) {
        return x.isObject(t) && t.isAxiosError === !0;
    }
    const Io = {
        Continue: 100,
        SwitchingProtocols: 101,
        Processing: 102,
        EarlyHints: 103,
        Ok: 200,
        Created: 201,
        Accepted: 202,
        NonAuthoritativeInformation: 203,
        NoContent: 204,
        ResetContent: 205,
        PartialContent: 206,
        MultiStatus: 207,
        AlreadyReported: 208,
        ImUsed: 226,
        MultipleChoices: 300,
        MovedPermanently: 301,
        Found: 302,
        SeeOther: 303,
        NotModified: 304,
        UseProxy: 305,
        Unused: 306,
        TemporaryRedirect: 307,
        PermanentRedirect: 308,
        BadRequest: 400,
        Unauthorized: 401,
        PaymentRequired: 402,
        Forbidden: 403,
        NotFound: 404,
        MethodNotAllowed: 405,
        NotAcceptable: 406,
        ProxyAuthenticationRequired: 407,
        RequestTimeout: 408,
        Conflict: 409,
        Gone: 410,
        LengthRequired: 411,
        PreconditionFailed: 412,
        PayloadTooLarge: 413,
        UriTooLong: 414,
        UnsupportedMediaType: 415,
        RangeNotSatisfiable: 416,
        ExpectationFailed: 417,
        ImATeapot: 418,
        MisdirectedRequest: 421,
        UnprocessableEntity: 422,
        Locked: 423,
        FailedDependency: 424,
        TooEarly: 425,
        UpgradeRequired: 426,
        PreconditionRequired: 428,
        TooManyRequests: 429,
        RequestHeaderFieldsTooLarge: 431,
        UnavailableForLegalReasons: 451,
        InternalServerError: 500,
        NotImplemented: 501,
        BadGateway: 502,
        ServiceUnavailable: 503,
        GatewayTimeout: 504,
        HttpVersionNotSupported: 505,
        VariantAlsoNegotiates: 506,
        InsufficientStorage: 507,
        LoopDetected: 508,
        NotExtended: 510,
        NetworkAuthenticationRequired: 511,
        WebServerIsDown: 521,
        ConnectionTimedOut: 522,
        OriginIsUnreachable: 523,
        TimeoutOccurred: 524,
        SslHandshakeFailed: 525,
        InvalidSslCertificate: 526,
    };
    Object.entries(Io).forEach(([t, e]) => {
        Io[e] = t;
    });
    function Ef(t) {
        const e = new or(t),
            r = X0(or.prototype.request, e);
        return (
            x.extend(r, or.prototype, e, { allOwnKeys: !0 }),
            x.extend(r, e, null, { allOwnKeys: !0 }),
            (r.create = function (s) {
                return Ef(hr(t, s));
            }),
            r
        );
    }
    const ye = Ef(Jn);
    ye.Axios = or;
    ye.CanceledError = Yn;
    ye.CancelToken = Dd;
    ye.isCancel = pf;
    ye.VERSION = wa;
    ye.toFormData = si;
    ye.AxiosError = j;
    ye.Cancel = ye.CanceledError;
    ye.all = function (e) {
        return Promise.all(e);
    };
    ye.spread = jd;
    ye.isAxiosError = Ud;
    ye.mergeConfig = hr;
    ye.AxiosHeaders = ke;
    ye.formToJSON = (t) => df(x.isHTMLForm(t) ? new FormData(t) : t);
    ye.getAdapter = vf.getAdapter;
    ye.HttpStatusCode = Io;
    ye.default = ye;
    const {
        Axios: X_,
        AxiosError: J_,
        CanceledError: Y_,
        isCancel: Q_,
        CancelToken: ex,
        VERSION: tx,
        all: rx,
        Cancel: nx,
        isAxiosError: sx,
        spread: ix,
        toFormData: ox,
        AxiosHeaders: ax,
        HttpStatusCode: lx,
        formToJSON: fx,
        getAdapter: cx,
        mergeConfig: ux,
        create: hx,
    } = ye;
    window.axios = ye;
    window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    const Fd = 84,
        Hd = 360,
        zd = [
            "000000000000000000000000000000000007ff8000000000000000000000000000000000000000000000000000",
            "0000000000000000000000037ffffc0007fffffc00000000000000000000000000000000000000000000000000",
            "00000000000000000000007fffffc3fffffffff9ff000000000000000000000000000000000000000000000000",
            "000000000000000000000fe07ffc7ffffffffffff000000001b800001e0000000000fc00000000000000000000",
            "0000000000000000000007ffffc03fffffffffff80000001fe7800000000000000007f00000000000000000000",
            "00000000000000001c1f71efff9fffffffffffff00000000fe00000000000000000000f8000000000000000000",
            "00000000000000070800040ffc01ffffffffffff800000003c2000000000000000000018000000000000000000",
            "000000000000003c0301cf9ff803fffffffffffe0000000000000000000007800000007ff00000000000000000",
            "0000000000000001fdc1cdfbe000007fffffffff00000000000000000007e0000003fffffc000007f800000000",
            "00000000000000003000003f4000001fffffffff0000000000000000000e0000003fffffc00000000000000000",
            "00000000000000ff0061ef9cdc00000ffffffffe00000000000000000038000007fffffffbe1e000e000000000",
            "00000000000001fbfee3ec3fe2000007fffffffc000000000000000000f0007807fffffffffff801f800000000",
            "80000000000000f7ffe0ee3fffd00007fffffffc000000000000000000f000f6fffffffffffff89bffe0000000",
            "00001ff8000000003ff80f1ffffe0007ffffffc800000000000fe000000001fbfffffffffffffffffffff00000",
            "00007fffff87fff1fff8f783c1ff80037ffffff00000000003fff8000000f1fbfffffffffffffffffffffff3fc",
            "8007ffffffffffffc0e023f3e07f0000ffffff00000000000fffffc000c7fe7dffffffffffffffffffffffffff",
            "f000fffffffffffffffffff7e19ff003ffffe000000000003ffffff8cffffff9ffffffffffffffffffffffffff",
            "fb813fffffffffffffffffff803ffc01ffff8000000000007ffff9f8fffffff7ffffffffffffffffffffffffff",
            "7f0ffffffffffffffffffffa003f1801fff00007fc000000ffc7fe0fffffffffffffffffffffffffffffffffff",
            "02011ffffffffffffffffff3c3bfc000ffe00003f8000001ff8ffe7ffffffffffffffffffffffffffffffffffc",
            "00c01fffffffffffffffff83200fe0007fe0000000000007ff3ffffffffffffffffffffffffffffffffffffffe",
            "0001ffffffffffffffffff00c20380003f8000000000003ffc7ffffffffffffffffffffffffffffffffffffffa",
            "0003fffffffffffffffffc0003f000001f8000000000007ff87fffffffffffffffffffffffffffffffff9cff80",
            "0001ffff3ffffffffffffc0003fc0000038000000000007ffc3ffffffffffffffffffffffffffffffffe03fe00",
            "00003fcc00fffffffffff80003fe3000000000000000007ffc06ffffffffffffffffffffffffffffff9e0e0000",
            "000001c0003ffffffffffe0003ff78000000000000018039f80fffffffffffffffffffffffffffff80001c0000",
            "000003700003fffffffffe0001fffc00000000000003c002f82fffffffffffffffffffffffffffff00007e0000",
            "00000c000000fffffffffff001fffc00000000000003800ef07ffffffffffffffffffffffffffffc0000fe0000",
            "000060000000fffffffffffc03ffff00000000000003c00dc07ffffffffffffffffffffffffffff00001fc0000",
            "0001000000007fffffffffffcfffffe000000000001ce0040efffffffffffffffffffffffffffff80000f80000",
            "0000000000011fffffffffffc7fffff000000000001c701fffffffffffffffffffffffffffffffffa000f00000",
            "0000000000000fffffffffffc7fffff0000000000018fcffffffffffffffffffffffffffffffffffa000c00000",
            "0000000000000ffffffffffff7ffffe8000000000000f8ffffffffffffffffffffffffffffffffffa000400000",
            "0000000000000bffffffffffffffff3000000000000187ffffffffffffffffffffffffffffffffffb000000000",
            "00000000000006ffffffffffffff8c340000000000003fffffffffffffffffffffffffffffffffff3000000000",
            "000000000000007ffffffffffffee07e000000000001ffffffffffffffffffffffffffffffffffff2000000000",
            "00000000000000bfffffffffffffe00a0000000000007ffffffffffffffffffffffffffffffffffe2000000000",
            "00000000000000ffffffffffffffe9000000000000003fffffffee3ffc7ffffffffffffffffffffc3000000000",
            "00000000000000fffffffffffffffe000000000000001fffbfffc67ff07ffffffffffffffffffff80000000000",
            "00000000000000ffffffffffffff30000000000000001fff1fff803fe3fffffffffffffffffffff02000000000",
            "00000000000000fffffffffffffc000000000000000e3fe3cfff000ff1ffffffffffffffffffffe07800000000",
            "00000000000000fffffffffffff8000000000000001ffe01c3ff0003f07ffffffffffffffffffe00f000000000",
            "00000000000000fffffffffffff8000000000000001ffc00f1ff0f87f83ffffffffffffffffffc000000000000",
            "00000000000000ffffffffffffc0000000000000001ff80c1df27ffffc7ffffffffffffffffbf800c000000000",
            "00000000000000ffffffffffffc0000000000000001ff00c08e3fffff87fffffffffffffffe27000c000000000",
            "000000000000007fffffffffff80000000000000001ff0000071fffff83fffffffffffffffc070008000000000",
            "000000000000007fffffffffff00000000000000001fe0006061fffffc3fffffffffffffffec38038000000000",
            "000000000000003fffffffffff00000000000000000207fe00206cfffffffffffffffffffff8380f8000000000",
            "000000000000001fffffffffff0000000000000000023ffe000000fffffffffffffffffffff0187f8000000000",
            "000000000000000ffffffffffe000000000000000007fffc000000fffffffffffffffffffff021d80000000000",
            "0000000000000003fffffffff800000000000000000ffffe000001fffffffffffffffffffff803c00000000000",
            "0000000000000001fffffffff000000000000000001fffffe06001fffffffffffffffffffff803000000000000",
            "0000000000000001bfffffffe000000000000000003ffffff0fe21fffffffffffffffffffffc02000000000000",
            "00000000000000009fffffffe000000000000000003ffffffefffffffffffffffffffffffff800000000000000",
            "00000000000000004ffff8606000000000000000003ffffffffff7fff3fffffffffffffffffc00000000000000",
            "00000000000000002ffff0006000000000000000007ffffffffffdfff1fffffffffffffffffc00000000000000",
            "000000000000000077ffe000700000000000000001fffffffffff8fff87ffffffffffffffff800000000000000",
            "000000000000000011ffe000340000000000000003fffffffffffcfffc07fffffffffffffff000000000000000",
            "000000000000000009ffe000100000000000000007fffffffffffe7ffd01ffffffffffffffe000000000000000",
            "000000000000000008ffc000000000000000000007fffffffffffe7ffe3801ffffffffffffe400000000000000",
            "0000000000000000007fc00000000000000000000fffffffffffff1ffffe00ffffffffffff8800000000000000",
            "0000000000000000003fc000b8000000000000000fffffffffffff1fffff003ffffefffffe0800000000000000",
            "0000000000000000001fe00006000000000000001fffffffffffff9ffffe003fffe0ffff600000000000000000",
            "0000000000000000003fe03801800000000000000fffffffffffff8ffffe0007ffe07ffe000000000000000000",
            "0000008000000000001ff078001c0000000000000fffffffffffff87fffc0007ff803ffc600000000000000000",
            "0000000000000000000ff9f0003f4000000000000fffffffffffffc7fff80007ff003ffc400000000000000000",
            "00000000000000000001fff000000000000000000fffffffffffffe3ffe00007fe003ffe000c00000000000000",
            "000000000000000000007ff000000000000000001fffffffffffffe1ff800007fc003fff000c00000000000000",
            "0000000000000000000001ff00000000000000001ffffffffffffff1ff000003f00003ff800c00000000000000",
            "0000000000000000000000ff80000000000000001ffffffffffffff9f8000003f00003ff800c00000000000000",
            "00000000000000000000003f00000000000000001ffffffffffffffde0000001f00003ff800300000000000000",
            "00000000000000000000000780000000000000001ffffffffffffffe00000001f000033f800080000000000000",
            "00000000000000000000000300100000000000000ffffffffffffffe06000000f000011f800240000000000000",
            "00000000000000000000000380f7ca000000000007ffffffffffffff7e000000f000020e001000000000000000",
            "000000000000000000000000c8f7fe000000000003fffffffffffffffe000000e8000204002040000000000000",
            "000000000000000000000000f3ffff000000000001fffffffffffffffc00000048000300000140000000000000",
            "00000000000000000000000013ffff800000000001fffffffffffffffc0000000c0001000002e0000000000000",
            "00000000000000000000000001ffffc000000000007ffffffffffffff80000000c0000c000c080000000000000",
            "00000000000000000000000003fffffe00000000003ff07ffffffffff8000000000000e000e000000000000000",
            "00000000000000000000000001ffffff000000000008003ffffffffff000000000000c6003e000000000000000",
            "00000000000000000000000001ffffff8000000000000003ffffffffe000000000000660078000000000000000",
            "00000000000000000000000007ffffff8000000000000003ffffffffc0000000000003301fc000000000000000",
            "00000000000000000000000007ffffffc000000000000007ffffffff80000000000001d07fc008000000000000",
            "0000000000000000000000000fffffffc000000000000007fffffffe00000000000001e07fcf08000000000000",
            "0000000000000000000000000fffffffe000000000000007fffffffc00000000000000f07fc000800000000000",
            "0000000000000000000000001ffffffffe00000000000007fffffffc00000000000000703f9c01c00000000000",
            "0000000000000000000000001ffffffffe80000000000003fffffff8000000000000007c3f9c004f0000000000",
            "0000000000000000000000000ffffffffff8000000000001fffffff0000000000000003c0314227fe000000000",
            "0000000000000000000000001ffffffffffc000000000001ffffffe0000000000000001c0002000ff800000000",
            "0000000000000000000000001fffffffffff800000000000ffffffe0000000000000000c00020023fcb0000000",
            "0000000000000000000000001fffffffffff800000000000ffffffe0000000000000000380000023ff01000000",
            "0000000000000000000000000fffffffffff8000000000007fffffe00000000000000001f8000003fe00200000",
            "00000000000000000000000007ffffffffff8000000000007fffffe00000000000000000026a2000e300000000",
            "00000000000000000000000007ffffffffff0000000000007ffffff000000000000000000010800001800c0000",
            "00000000000000000000000003ffffffffff0000000000007ffffff00000000000000000000000000020000000",
            "00000000000000000000000001fffffffffe0000000000003ffffff00000000000000000000000802000000000",
            "00000000000000000000000001fffffffffc0000000000007ffffff80400000000000000000001f82000000000",
            "00000000000000000000000000fffffffff80000000000007ffffff80400000000000000000003f03000000000",
            "00000000000000000000000000fffffffff8000000000000fffffff80c00000000000000000037f03800000000",
            "000000000000000000000000007ffffffff8000000000000fffffff01c000000000000000000fff03800000000",
            "000000000000000000000000001ffffffff8000000000000fffffff0fc000000000000000000fffc7c00001001",
            "0000000000000000000000000007fffffff8000000000000ffffffc0f8000000000000000003ffff7c00000002",
            "0000000000000000000000000003fffffff0000000000000ffffff00f8000000000000000003fffffc00000000",
            "0000000000000000000000000003fffffff00000000000007ffffe00f8000000000000000007fffffe00000000",
            "0000000000000000000000000003fffffff00000000000007ffffe00f800000000000000003fffffff80008000",
            "0000000000000000000000000003ffffffe00000000000003ffffe01f00000000000000001ffffffff80004000",
            "0000000000000000000000000003ffffffc00000000000003fffff01f00000000000000003ffffffffc0000000",
            "0000000000000000000000000003fffffe000000000000003ffffe00f00000000000000003ffffffffe0000000",
            "0000000000000000000000000007fffff8000000000000001ffffe00e00000000000000007fffffffff0000000",
            "0000000000000000000000000007fffff0000000000000001ffff800400000000000000003fffffffff8000000",
            "0000000000000000000000000007ffffe0000000000000001ffff800000000000000000007fffffffff8000000",
            "0000000000000000000000000007ffffe0000000000000001ffff800000000000000000003fffffffff8000000",
            "0000000000000000000000000007ffffe0000000000000000ffff000000000000000000001fffffffffc000000",
            "0000000000000000000000000007ffffc00000000000000007ffe000000000000000000001fffffffff8000000",
            "000000000000000000000000000fffffc00000000000000007ffe000000000000000000001fffffffff8000000",
            "000000000000000000000000000fffff800000000000000003ffc000000000000000000000fffffffff8000000",
            "0000000000000000000000000007ffff000000000000000003ff8000000000000000000000ffc03ffff0000000",
            "000000000000000000000000000ffffe000000000000000003fe0000000000000000000001ff001bffe0000000",
            "000000000000000000000000000fffdc000000000000000001800000000000000000000000e00017ffe0000000",
            "000000000000000000000000001fffe0000000000000000000000000000000000000000000000003ffc0000040",
            "000000000000000000000000001fffe0000000000000000000000000000000000000000000000000ffc0000020",
            "000000000000000000000000003fffe0000000000000000000000000000000000000000000000000ffc0000010",
            "000000000000000000000000001fff80000000000000000000000000000000000000000000000000160000001c",
            "000000000000000000000000001ffc000000000000000000000000000000000000000000000000000000000038",
            "000000000000000000000000003ffc000000000000000000000000000000000000000000000000000000000018",
            "000000000000000000000000003fe00000000000000000000000000000000000000000000000000007000000d0",
            "000000000000000000000000003ff80000000000000000000000000000000000000000000000000007000001c0",
            "000000000000000000000000001fe0000000000000000000000000000000000000000000000000000200000380",
            "000000000000000000000000003fe0000000000000000000000000000000000000000000000000000000000e00",
            "000000000000000000000000007f80000000000000000000000000000000000000000000000000000000001e00",
            "000000000000000000000000007f80000000000000000000000000000000000000000000000000000000000c00",
            "000000000000000000000000007fc0000000000000000000000000000000000000000000000000000000000000",
            "00000000000000000000000000ff80000000000000000000000000000000000000000000000000000000000000",
            "00000000000000000000000000ff00000000000000000000000000000000004000000000000000000000000000",
            "000000000000000000000000007e00000000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000007e01c00000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000003c00000000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000003f00000000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000000fc0000000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000000100000000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000",
            "000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000",
        ],
        Kd = window.matchMedia("(min-width: 1024px)"),
        Ll = (t, e, r) => Math.min(r, Math.max(e, t)),
        Wd = (t, e, r) => t.map((n, s) => Math.round(n + (e[s] - n) * r)),
        Vd = (t) =>
            t
                .split("")
                .map((e) => parseInt(e, 16).toString(2).padStart(4, "0"))
                .join("");
    class Gd {
        constructor(e) {
            ((this.root = e),
                (this.canvas = e.querySelector("canvas")),
                (this.context = this.canvas.getContext("2d")),
                (this.dotLayer = document.createElement("canvas")),
                (this.land = zd.map(Vd)),
                (this.pins = [...e.querySelectorAll("[data-pin]")].map((r) => ({
                    element: r,
                    lat: Number(r.dataset.lat),
                    lon: Number(r.dataset.lon),
                    x: 0,
                    y: 0,
                }))),
                (this.curves = []),
                (this.pulses = []),
                (this.nextPulse = 0),
                (this.visible = !0),
                (this.frame = 0),
                (this.motion = window.matchMedia(
                    "(prefers-reduced-motion: reduce)",
                )),
                new ResizeObserver(() => this.layout()).observe(e),
                new MutationObserver(() => this.layout()).observe(
                    document.documentElement,
                    { attributes: !0, attributeFilter: ["data-theme"] },
                ),
                new IntersectionObserver(([r]) => {
                    ((this.visible = r.isIntersecting), this.sync());
                }).observe(e),
                this.motion.addEventListener("change", () => this.sync()),
                this.layout());
        }
        layout() {
            const e = this.root.clientWidth,
                r = this.root.clientHeight;
            if (!(!e || !r)) {
                ((this.width = e),
                    (this.height = r),
                    (this.ratio = Math.min(window.devicePixelRatio || 1, 2)));
                for (const n of [this.canvas, this.dotLayer])
                    ((n.width = Math.round(e * this.ratio)),
                        (n.height = Math.round(r * this.ratio)));
                (this.readColours(),
                    this.project(),
                    this.link(),
                    this.paintDots(),
                    this.placePins(),
                    this.sync(),
                    this.draw(performance.now()),
                    this.root.classList.add("is-ready"));
            }
        }
        readColours() {
            const e = getComputedStyle(this.root),
                r = (n, s) => {
                    const i = e
                        .getPropertyValue(n)
                        .trim()
                        .split(/\s+/)
                        .map(Number);
                    return i.length === 3 && i.every(Number.isFinite) ? i : s;
                };
            this.colours = {
                dot: r("--map-dot", [21, 61, 52]),
                hot: r("--map-hot", [96, 150, 52]),
                arc: r("--map-arc", [21, 61, 52]),
                pulse: r("--map-pulse", [150, 200, 60]),
                dotAlpha: Number(e.getPropertyValue("--map-dot-alpha")) || 0.2,
            };
        }
        project() {
            const { width: e, height: r } = this,
                n = Kd.matches,
                s = e * (n ? 0.49 : 0.1),
                i = e * (n ? 0.96 : 0.9),
                o = this.pins.map((b) => b.lon),
                a = this.pins.map((b) => b.lat),
                l = o.length ? Math.min(...o) : -15,
                f = o.length ? Math.max(...o) : 45,
                u = a.length ? Math.min(...a) : 28,
                d = a.length ? Math.max(...a) : 58,
                h = Math.max(f - l, 10) + 12,
                g = Math.max(d - u, 10) + 16;
            this.scale = Ll(
                Math.min((i - s) / h, (r * 0.8) / g),
                1.2,
                n ? 13 : 10,
            );
            const v = Math.max(0, i - s - h * this.scale);
            ((this.originX = s + v / 2 - (l - 6) * this.scale),
                (this.originY = r / 2 + ((u + d) / 2) * this.scale));
            for (const b of this.pins)
                ((b.x = this.originX + b.lon * this.scale),
                    (b.y = this.originY - b.lat * this.scale));
        }
        link() {
            const { pins: e } = this,
                r = (l, f) => Math.hypot(l.x - f.x, l.y - f.y),
                n = [],
                s = new Set(),
                i = (l, f) => {
                    const u = l < f ? `${l}-${f}` : `${f}-${l}`;
                    s.has(u) || (s.add(u), n.push([l, f]));
                },
                o = new Set(e.length ? [0] : []);
            for (; o.size < e.length; ) {
                let l = null;
                for (const f of o)
                    for (let u = 0; u < e.length; u++) {
                        if (o.has(u)) continue;
                        const d = r(e[f], e[u]);
                        (!l || d < l.d) && (l = { i: f, j: u, d });
                    }
                (i(l.i, l.j), o.add(l.j));
            }
            const a = e.map(
                (l, f) => n.filter(([u, d]) => u === f || d === f).length,
            );
            (e.forEach((l, f) => {
                if (a[f] !== 1) return;
                const u = e
                    .map((d, h) => ({ j: h, d: r(l, d) }))
                    .filter(
                        ({ j: d }) =>
                            d !== f &&
                            !s.has(f < d ? `${f}-${d}` : `${d}-${f}`),
                    )
                    .sort((d, h) => d.d - h.d)[0];
                u && i(f, u.j);
            }),
                (this.curves = n.map(([l, f]) => {
                    const u = e[l],
                        d = e[f],
                        h = Math.hypot(d.x - u.x, d.y - u.y) || 1;
                    let g = (d.y - u.y) / h,
                        v = -(d.x - u.x) / h;
                    return (
                        v > 0 && ((g = -g), (v = -v)),
                        {
                            a: u,
                            b: d,
                            c: {
                                x: (u.x + d.x) / 2 + g * h * 0.24,
                                y: (u.y + d.y) / 2 + v * h * 0.24,
                            },
                        }
                    );
                })),
                (this.pulses = []));
        }
        paintDots() {
            const e = this.dotLayer.getContext("2d");
            (e.setTransform(this.ratio, 0, 0, this.ratio, 0, 0),
                e.clearRect(0, 0, this.width, this.height));
            const { dot: r, hot: n, dotAlpha: s } = this.colours,
                i = Math.max(1, Math.ceil(5 / this.scale)),
                o = Ll(this.scale * i * 0.2, 0.7, 2.4),
                a = Math.max(60, this.scale * 16);
            for (let l = 0; l < this.land.length; l += i) {
                const f = this.originY - (Fd - l - 0.5) * this.scale;
                if (f < -o || f > this.height + o) continue;
                const u = this.land[l];
                for (let d = 0; d < Hd; d += i) {
                    if (u[d] !== "1") continue;
                    const h = this.originX + (d - 180 + 0.5) * this.scale;
                    if (h < -o || h > this.width + o) continue;
                    let g = 0;
                    for (const y of this.pins)
                        g = Math.max(g, 1 - Math.hypot(h - y.x, f - y.y) / a);
                    const [v, b, _] = Wd(r, n, g);
                    ((e.fillStyle = `rgb(${v} ${b} ${_} / ${(s + g * 0.55).toFixed(2)})`),
                        e.beginPath(),
                        e.arc(h, f, o * (1 + g * 0.35), 0, Math.PI * 2),
                        e.fill());
                }
            }
        }
        placePins() {
            for (const e of this.pins) {
                const r =
                    e.x > 8 &&
                    e.x < this.width - 8 &&
                    e.y > 8 &&
                    e.y < this.height - 8;
                ((e.element.hidden = !r),
                    (e.element.style.left = `${e.x.toFixed(1)}px`),
                    (e.element.style.top = `${e.y.toFixed(1)}px`),
                    (e.element.dataset.edge =
                        e.x > this.width * 0.7
                            ? "end"
                            : e.x < this.width * 0.3
                              ? "start"
                              : "middle"));
            }
        }
        pointOn({ a: e, b: r, c: n }, s) {
            const i = 1 - s;
            return {
                x: i * i * e.x + 2 * i * s * n.x + s * s * r.x,
                y: i * i * e.y + 2 * i * s * n.y + s * s * r.y,
            };
        }
        draw(e) {
            const { context: r, colours: n } = this;
            (r.setTransform(this.ratio, 0, 0, this.ratio, 0, 0),
                r.clearRect(0, 0, this.width, this.height),
                r.drawImage(this.dotLayer, 0, 0, this.width, this.height),
                (r.lineWidth = 1),
                (r.strokeStyle = `rgb(${n.arc.join(" ")} / 0.3)`));
            for (const { a: s, b: i, c: o } of this.curves)
                (r.beginPath(),
                    r.moveTo(s.x, s.y),
                    r.quadraticCurveTo(o.x, o.y, i.x, i.y),
                    r.stroke());
            if (this.frame) {
                (this.curves.length &&
                    this.pulses.length < 3 &&
                    e >= this.nextPulse &&
                    (this.pulses.push({
                        curve: this.curves[
                            Math.floor(Math.random() * this.curves.length)
                        ],
                        reverse: Math.random() < 0.5,
                        start: e,
                        duration: 1500 + Math.random() * 1300,
                    }),
                    (this.nextPulse = e + 450 + Math.random() * 900)),
                    (this.pulses = this.pulses.filter(
                        (s) => e - s.start < s.duration,
                    )));
                for (const s of this.pulses) {
                    const i = (e - s.start) / s.duration,
                        o = i,
                        a = Math.max(0, i - 0.3),
                        l = 14;
                    ((r.lineCap = "round"), (r.lineWidth = 2));
                    for (let u = 0; u < l; u++) {
                        const d = a + ((o - a) * u) / l,
                            h = a + ((o - a) * (u + 1)) / l,
                            g = this.pointOn(s.curve, s.reverse ? 1 - d : d),
                            v = this.pointOn(s.curve, s.reverse ? 1 - h : h);
                        ((r.strokeStyle = `rgb(${n.pulse.join(" ")} / ${((u + 1) / l) * 0.9})`),
                            r.beginPath(),
                            r.moveTo(g.x, g.y),
                            r.lineTo(v.x, v.y),
                            r.stroke());
                    }
                    const f = this.pointOn(s.curve, s.reverse ? 1 - o : o);
                    ((r.fillStyle = `rgb(${n.pulse.join(" ")})`),
                        (r.shadowColor = `rgb(${n.pulse.join(" ")})`),
                        (r.shadowBlur = 10),
                        r.beginPath(),
                        r.arc(f.x, f.y, 2.6, 0, Math.PI * 2),
                        r.fill(),
                        (r.shadowBlur = 0));
                }
            }
        }
        sync() {
            const e =
                this.visible && !this.motion.matches && this.curves.length > 0;
            if (e && !this.frame) {
                const r = (n) => {
                    (this.draw(n), (this.frame = requestAnimationFrame(r)));
                };
                this.frame = requestAnimationFrame(r);
            } else
                !e &&
                    this.frame &&
                    (cancelAnimationFrame(this.frame),
                    (this.frame = 0),
                    (this.pulses = []),
                    this.draw(performance.now()));
        }
    }
    document.querySelectorAll("[data-hero-map]").forEach((t) => {
        t.querySelector("canvas") && new Gd(t);
    });
    var Po = !1,
        Mo = !1,
        ar = [],
        Bo = -1,
        _a = !1;
    function Zd(t) {
        Yd(t);
    }
    function Xd() {
        _a = !0;
    }
    function Jd() {
        ((_a = !1), _f());
    }
    function Yd(t) {
        (ar.includes(t) || ar.push(t), _f());
    }
    function Qd(t) {
        let e = ar.indexOf(t);
        e !== -1 && e > Bo && ar.splice(e, 1);
    }
    function _f() {
        if (!Mo && !Po) {
            if (_a) return;
            ((Po = !0), queueMicrotask(ep));
        }
    }
    function ep() {
        ((Po = !1), (Mo = !0));
        for (let t = 0; t < ar.length; t++) (ar[t](), (Bo = t));
        ((ar.length = 0), (Bo = -1), (Mo = !1));
    }
    var tn,
        wr,
        rn,
        xf,
        $o = !0;
    function tp(t) {
        (($o = !1), t(), ($o = !0));
    }
    function rp(t) {
        ((tn = t.reactive),
            (rn = t.release),
            (wr = (e) =>
                t.effect(e, {
                    scheduler: (r) => {
                        $o ? Zd(r) : r();
                    },
                })),
            (xf = t.raw));
    }
    function Rl(t) {
        wr = t;
    }
    function np(t) {
        let e = () => {};
        return [
            (n) => {
                let s = wr(n);
                return (
                    t._x_effects ||
                        ((t._x_effects = new Set()),
                        (t._x_runEffects = () => {
                            t._x_effects.forEach((i) => i());
                        })),
                    t._x_effects.add(s),
                    (e = () => {
                        s !== void 0 && (t._x_effects.delete(s), rn(s));
                    }),
                    s
                );
            },
            () => {
                e();
            },
        ];
    }
    function Af(t, e) {
        let r = !0,
            n,
            s,
            i = wr(() => {
                let o = t(),
                    a = JSON.stringify(o);
                if (!r && (typeof o == "object" || o !== n)) {
                    let l = typeof n == "object" ? JSON.parse(s) : n;
                    queueMicrotask(() => {
                        e(o, l);
                    });
                }
                ((n = o), (s = a), (r = !1));
            });
        return () => rn(i);
    }
    async function sp(t) {
        Xd();
        try {
            (await t(), await Promise.resolve());
        } finally {
            Jd();
        }
    }
    var Sf = [],
        Tf = [],
        Nf = [];
    function ip(t) {
        Nf.push(t);
    }
    function xa(t, e) {
        typeof e == "function"
            ? (t._x_cleanups || (t._x_cleanups = []), t._x_cleanups.push(e))
            : ((e = t), Tf.push(e));
    }
    function Of(t) {
        Sf.push(t);
    }
    function Cf(t, e, r) {
        (t._x_attributeCleanups || (t._x_attributeCleanups = {}),
            t._x_attributeCleanups[e] || (t._x_attributeCleanups[e] = []),
            t._x_attributeCleanups[e].push(r));
    }
    function Lf(t, e) {
        t._x_attributeCleanups &&
            Object.entries(t._x_attributeCleanups).forEach(([r, n]) => {
                (e === void 0 || e.includes(r)) &&
                    (n.forEach((s) => s()), delete t._x_attributeCleanups[r]);
            });
    }
    function op(t) {
        for (t._x_effects?.forEach(Qd); t._x_cleanups?.length; )
            t._x_cleanups.pop()();
    }
    var Aa = new MutationObserver(Oa),
        Sa = !1;
    function Ta() {
        (Aa.observe(document, {
            subtree: !0,
            childList: !0,
            attributes: !0,
            attributeOldValue: !0,
        }),
            (Sa = !0));
    }
    function Rf() {
        (ap(), Aa.disconnect(), (Sa = !1));
    }
    var An = [];
    function ap() {
        let t = Aa.takeRecords();
        An.push(() => t.length > 0 && Oa(t));
        let e = An.length;
        queueMicrotask(() => {
            if (An.length === e) for (; An.length > 0; ) An.shift()();
        });
    }
    function be(t) {
        if (!Sa) return t();
        Rf();
        let e = t();
        return (Ta(), e);
    }
    var Na = !1,
        Vs = [];
    function lp() {
        Na = !0;
    }
    function fp() {
        ((Na = !1), Oa(Vs), (Vs = []));
    }
    function Oa(t) {
        if (Na) {
            Vs = Vs.concat(t);
            return;
        }
        let e = [],
            r = new Set(),
            n = new Map(),
            s = new Map();
        for (let i = 0; i < t.length; i++)
            if (
                !t[i].target._x_ignoreMutationObserver &&
                (t[i].type === "childList" &&
                    (t[i].removedNodes.forEach((o) => {
                        o.nodeType === 1 && o._x_marker && r.add(o);
                    }),
                    t[i].addedNodes.forEach((o) => {
                        if (o.nodeType === 1) {
                            if (r.has(o)) {
                                r.delete(o);
                                return;
                            }
                            o._x_marker || e.push(o);
                        }
                    })),
                t[i].type === "attributes")
            ) {
                let o = t[i].target,
                    a = t[i].attributeName,
                    l = t[i].oldValue,
                    f = () => {
                        (n.has(o) || n.set(o, []),
                            n
                                .get(o)
                                .push({ name: a, value: o.getAttribute(a) }));
                    },
                    u = () => {
                        (s.has(o) || s.set(o, []), s.get(o).push(a));
                    };
                o.hasAttribute(a) && l === null
                    ? f()
                    : o.hasAttribute(a)
                      ? (u(), f())
                      : u();
            }
        (s.forEach((i, o) => {
            Lf(o, i);
        }),
            n.forEach((i, o) => {
                Sf.forEach((a) => a(o, i));
            }));
        for (let i of r)
            e.some((o) => o.contains(i)) || Tf.forEach((o) => o(i));
        for (let i of e) i.isConnected && Nf.forEach((o) => o(i));
        ((e = null), (r = null), (n = null), (s = null));
    }
    function qf(t) {
        return pr(dr(t));
    }
    function Qn(t, e, r) {
        return (
            (t._x_dataStack = [e, ...dr(r || t)]),
            () => {
                t._x_dataStack = t._x_dataStack.filter((n) => n !== e);
            }
        );
    }
    function dr(t) {
        return t._x_dataStack
            ? t._x_dataStack
            : typeof ShadowRoot == "function" && t instanceof ShadowRoot
              ? dr(t.host)
              : t.parentNode
                ? dr(t.parentNode)
                : [];
    }
    function pr(t) {
        return new Proxy({ objects: t }, cp);
    }
    function kf(t, e) {
        return t === null || t === Object.prototype
            ? null
            : Object.prototype.hasOwnProperty.call(t, e)
              ? t
              : kf(Object.getPrototypeOf(t), e);
    }
    var cp = {
        ownKeys({ objects: t }) {
            return Array.from(new Set(t.flatMap((e) => Object.keys(e))));
        },
        has({ objects: t }, e) {
            return e == Symbol.unscopables
                ? !1
                : t.some(
                      (r) =>
                          Object.prototype.hasOwnProperty.call(r, e) ||
                          Reflect.has(r, e),
                  );
        },
        get({ objects: t }, e, r) {
            return e == "toJSON"
                ? up
                : Reflect.get(t.find((n) => Reflect.has(n, e)) || {}, e, r);
        },
        set({ objects: t }, e, r, n) {
            let s;
            for (const o of t) if (((s = kf(o, e)), s)) break;
            s || (s = t[t.length - 1]);
            const i = Object.getOwnPropertyDescriptor(s, e);
            return i?.set && i?.get
                ? i.set.call(n, r) || !0
                : Reflect.set(s, e, r);
        },
    };
    function up() {
        return Reflect.ownKeys(this).reduce(
            (e, r) => ((e[r] = Reflect.get(this, r)), e),
            {},
        );
    }
    function Ca(t) {
        let e = (n) => typeof n == "object" && !Array.isArray(n) && n !== null,
            r = (n, s = "") => {
                Object.entries(Object.getOwnPropertyDescriptors(n)).forEach(
                    ([i, { value: o, enumerable: a }]) => {
                        if (
                            a === !1 ||
                            o === void 0 ||
                            (typeof o == "object" && o !== null && o.__v_skip)
                        )
                            return;
                        let l = s === "" ? i : `${s}.${i}`;
                        typeof o == "object" && o !== null && o._x_interceptor
                            ? (n[i] = o.initialize(t, l, i))
                            : e(o) &&
                              o !== n &&
                              !(o instanceof Element) &&
                              r(o, l);
                    },
                );
            };
        return r(t);
    }
    function If(t, e = () => {}) {
        let r = {
            initialValue: void 0,
            _x_interceptor: !0,
            initialize(n, s, i) {
                return t(
                    this.initialValue,
                    () => hp(n, s),
                    (o) => Do(n, s, o),
                    s,
                    i,
                );
            },
        };
        return (
            e(r),
            (n) => {
                if (typeof n == "object" && n !== null && n._x_interceptor) {
                    let s = r.initialize.bind(r);
                    r.initialize = (i, o, a) => {
                        let l = n.initialize(i, o, a);
                        return ((r.initialValue = l), s(i, o, a));
                    };
                } else r.initialValue = n;
                return r;
            }
        );
    }
    function hp(t, e) {
        return e.split(".").reduce((r, n) => r[n], t);
    }
    function Do(t, e, r) {
        if ((typeof e == "string" && (e = e.split(".")), e.length === 1))
            t[e[0]] = r;
        else {
            if (e.length === 0) throw error;
            return (t[e[0]] || (t[e[0]] = {}), Do(t[e[0]], e.slice(1), r));
        }
    }
    var Pf = {};
    function at(t, e) {
        Pf[t] = e;
    }
    function jn(t, e) {
        let r = dp(e);
        return (
            Object.entries(Pf).forEach(([n, s]) => {
                Object.defineProperty(t, `$${n}`, {
                    get() {
                        return s(e, r);
                    },
                    enumerable: !1,
                });
            }),
            t
        );
    }
    function dp(t) {
        let [e, r] = Ff(t),
            n = { interceptor: If, ...e };
        return (xa(t, r), n);
    }
    function pp(t, e, r, ...n) {
        try {
            return r(...n);
        } catch (s) {
            Un(s, t, e);
        }
    }
    function Un(...t) {
        return Mf(...t);
    }
    var Mf = mp;
    function gp(t) {
        Mf = t;
    }
    function mp(t, e, r = void 0) {
        ((t = Object.assign(t ?? { message: "No error message given." }, {
            el: e,
            expression: r,
        })),
            console.warn(
                `Alpine Expression Error: ${t.message}

${
    r
        ? 'Expression: "' +
          r +
          `"

`
        : ""
}`,
                e,
            ),
            setTimeout(() => {
                throw t;
            }, 0));
    }
    var zr = !0;
    function Bf(t) {
        let e = zr;
        zr = !1;
        let r = t();
        return ((zr = e), r);
    }
    function lr(t, e, r = {}) {
        let n;
        return (Me(t, e)((s) => (n = s), r), n);
    }
    function Me(...t) {
        return $f(...t);
    }
    var $f = () => {};
    function bp(t) {
        $f = t;
    }
    var Df;
    function yp(t) {
        Df = t;
    }
    function vp(t, e) {
        let r = {};
        jn(r, t);
        let n = [r, ...dr(t)],
            s = typeof e == "function" ? wp(n, e) : _p(n, e, t);
        return pp.bind(null, t, e, s);
    }
    function wp(t, e) {
        return (
            r = () => {},
            { scope: n = {}, params: s = [], context: i } = {},
        ) => {
            if (!zr) {
                Fn(r, e, pr([n, ...t]), s);
                return;
            }
            let o = e.apply(pr([n, ...t]), s);
            Fn(r, o);
        };
    }
    var uo = {};
    function Ep(t, e) {
        if (uo[t]) return uo[t];
        let r = Object.getPrototypeOf(async function () {}).constructor,
            n =
                /^[\n\s]*if.*\(.*\)/.test(t.trim()) ||
                /^(let|const)\s/.test(t.trim())
                    ? `(async()=>{ ${t} })()`
                    : t,
            i = (() => {
                try {
                    let o = new r(
                        ["__self", "scope"],
                        `with (scope) { __self.result = ${n} }; __self.finished = true; return __self.result;`,
                    );
                    return (
                        Object.defineProperty(o, "name", {
                            value: `[Alpine] ${t}`,
                        }),
                        o
                    );
                } catch (o) {
                    return (Un(o, e, t), Promise.resolve());
                }
            })();
        return ((uo[t] = i), i);
    }
    function _p(t, e, r) {
        let n = Ep(e, r);
        return (
            s = () => {},
            { scope: i = {}, params: o = [], context: a } = {},
        ) => {
            ((n.result = void 0), (n.finished = !1));
            let l = pr([i, ...t]);
            if (typeof n == "function") {
                let f = n.call(a, n, l).catch((u) => Un(u, r, e));
                n.finished
                    ? (Fn(s, n.result, l, o, r), (n.result = void 0))
                    : f
                          .then((u) => {
                              Fn(s, u, l, o, r);
                          })
                          .catch((u) => Un(u, r, e))
                          .finally(() => (n.result = void 0));
            }
        };
    }
    function Fn(t, e, r, n, s) {
        if (zr && typeof e == "function") {
            let i = e.apply(r, n);
            i instanceof Promise
                ? i.then((o) => Fn(t, o, r, n)).catch((o) => Un(o, s, e))
                : t(i);
        } else
            typeof e == "object" && e instanceof Promise
                ? e.then((i) => t(i))
                : t(e);
    }
    function xp(...t) {
        return Df(...t);
    }
    function Ap(t, e, r = {}) {
        let n = {};
        jn(n, t);
        let s = [n, ...dr(t)],
            i = pr([r.scope ?? {}, ...s]),
            o = r.params ?? [];
        if (e.includes("await")) {
            let a = Object.getPrototypeOf(async function () {}).constructor,
                l =
                    /^[\n\s]*if.*\(.*\)/.test(e.trim()) ||
                    /^(let|const)\s/.test(e.trim())
                        ? `(async()=>{ ${e} })()`
                        : e;
            return new a(
                ["scope"],
                `with (scope) { let __result = ${l}; return __result }`,
            ).call(r.context, i);
        } else {
            let a =
                    /^[\n\s]*if.*\(.*\)/.test(e.trim()) ||
                    /^(let|const)\s/.test(e.trim())
                        ? `(()=>{ ${e} })()`
                        : e,
                f = new Function(
                    ["scope"],
                    `with (scope) { let __result = ${a}; return __result }`,
                ).call(r.context, i);
            return typeof f == "function" && zr ? f.apply(i, o) : f;
        }
    }
    var La = "x-";
    function nn(t = "") {
        return La + t;
    }
    function Sp(t) {
        La = t;
    }
    var Gs = {};
    function Ee(t, e) {
        return (
            (Gs[t] = e),
            {
                before(r) {
                    if (!Gs[r]) {
                        console.warn(
                            String.raw`Cannot find directive \`${r}\`. \`${t}\` will use the default order of execution`,
                        );
                        return;
                    }
                    const n = ir.indexOf(r);
                    ir.splice(n >= 0 ? n : ir.indexOf("DEFAULT"), 0, t);
                },
            }
        );
    }
    function Tp(t) {
        return Object.keys(Gs).includes(t);
    }
    function Ra(t, e, r) {
        if (((e = Array.from(e)), t._x_virtualDirectives)) {
            let i = Object.entries(t._x_virtualDirectives).map(([a, l]) => ({
                    name: a,
                    value: l,
                })),
                o = jf(i);
            ((i = i.map((a) =>
                o.find((l) => l.name === a.name)
                    ? { name: `x-bind:${a.name}`, value: `"${a.value}"` }
                    : a,
            )),
                (e = e.concat(i)));
        }
        let n = {};
        return e
            .map(Kf((i, o) => (n[i] = o)))
            .filter(Vf)
            .map(Cp(n, r))
            .sort(Lp)
            .map((i) => Op(t, i));
    }
    function jf(t) {
        return Array.from(t)
            .map(Kf())
            .filter((e) => !Vf(e));
    }
    var jo = !1,
        Rn = new Map(),
        Uf = Symbol();
    function Np(t) {
        jo = !0;
        let e = Symbol();
        ((Uf = e), Rn.set(e, []));
        let r = () => {
                for (; Rn.get(e).length; ) Rn.get(e).shift()();
                Rn.delete(e);
            },
            n = () => {
                ((jo = !1), r());
            };
        (t(r), n());
    }
    function Ff(t) {
        let e = [],
            r = (a) => e.push(a),
            [n, s] = np(t);
        return (
            e.push(s),
            [
                {
                    Alpine: on,
                    effect: n,
                    cleanup: r,
                    evaluateLater: Me.bind(Me, t),
                    evaluate: lr.bind(lr, t),
                },
                () => e.forEach((a) => a()),
            ]
        );
    }
    function Op(t, e) {
        let r = () => {},
            n = Gs[e.type] || r,
            [s, i] = Ff(t);
        Cf(t, e.original, i);
        let o = () => {
            t._x_ignore ||
                t._x_ignoreSelf ||
                (n.inline && n.inline(t, e, s),
                (n = n.bind(n, t, e, s)),
                jo ? Rn.get(Uf).push(n) : n());
        };
        return ((o.runCleanups = i), o);
    }
    var Hf =
            (t, e) =>
            ({ name: r, value: n }) => (
                r.startsWith(t) && (r = r.replace(t, e)),
                { name: r, value: n }
            ),
        zf = (t) => t;
    function Kf(t = () => {}) {
        return ({ name: e, value: r }) => {
            let { name: n, value: s } = Wf.reduce((i, o) => o(i), {
                name: e,
                value: r,
            });
            return (n !== e && t(n, e), { name: n, value: s });
        };
    }
    var Wf = [];
    function qa(t) {
        Wf.push(t);
    }
    function Vf({ name: t }) {
        return Gf().test(t);
    }
    var Gf = () => new RegExp(`^${La}([^:^.]+)\\b`);
    function Cp(t, e) {
        return ({ name: r, value: n }) => {
            r === n && (n = "");
            let s = r.match(Gf()),
                i = r.match(/:([a-zA-Z0-9\-_:]+)/),
                o = r.match(/\.[^.\]]+(?=[^\]]*$)/g) || [],
                a = e || t[r] || r;
            return {
                type: s ? s[1] : null,
                value: i ? i[1] : null,
                modifiers: o.map((l) => l.replace(".", "")),
                expression: n,
                original: a,
            };
        };
    }
    var Uo = "DEFAULT",
        ir = [
            "ignore",
            "ref",
            "id",
            "data",
            "anchor",
            "bind",
            "init",
            "for",
            "model",
            "modelable",
            "transition",
            "show",
            "if",
            Uo,
            "teleport",
        ];
    function Lp(t, e) {
        let r = ir.indexOf(t.type) === -1 ? Uo : t.type,
            n = ir.indexOf(e.type) === -1 ? Uo : e.type;
        return ir.indexOf(r) - ir.indexOf(n);
    }
    function Pn(t, e, r = {}, n = {}) {
        return t.dispatchEvent(
            new CustomEvent(e, {
                detail: r,
                bubbles: !0,
                composed: !0,
                cancelable: !0,
                ...n,
            }),
        );
    }
    function gr(t, e) {
        if (typeof ShadowRoot == "function" && t instanceof ShadowRoot) {
            Array.from(t.children).forEach((s) => gr(s, e));
            return;
        }
        let r = !1;
        if ((e(t, () => (r = !0)), r)) return;
        let n = t.firstElementChild;
        for (; n; ) (gr(n, e), (n = n.nextElementSibling));
    }
    function bt(t, ...e) {
        console.warn(`Alpine Warning: ${t}`, ...e);
    }
    var ql = !1;
    function Rp() {
        (ql &&
            bt(
                "Alpine has already been initialized on this page. Calling Alpine.start() more than once can cause problems.",
            ),
            (ql = !0),
            document.body ||
                bt(
                    "Unable to initialize. Trying to load Alpine before `<body>` is available. Did you forget to add `defer` in Alpine's `<script>` tag?",
                ),
            Pn(document, "alpine:init"),
            Pn(document, "alpine:initializing"),
            Ta(),
            ip((e) => Ot(e, gr)),
            xa((e) => sn(e)),
            Of((e, r) => {
                Ra(e, r).forEach((n) => n());
            }));
        let t = (e) => !oi(e.parentElement, !0);
        (Array.from(document.querySelectorAll(Jf().join(",")))
            .filter(t)
            .forEach((e) => {
                Ot(e);
            }),
            Pn(document, "alpine:initialized"),
            setTimeout(() => {
                Pp();
            }));
    }
    var ka = [],
        Zf = [];
    function Xf() {
        return ka.map((t) => t());
    }
    function Jf() {
        return ka.concat(Zf).map((t) => t());
    }
    function Yf(t) {
        ka.push(t);
    }
    function Qf(t) {
        Zf.push(t);
    }
    function oi(t, e = !1) {
        return Nt(t, (r) => {
            if ((e ? Jf() : Xf()).some((s) => r.matches(s))) return !0;
        });
    }
    function Nt(t, e) {
        if (t) {
            if (e(t)) return t;
            if (t._x_teleportBack) return Nt(t._x_teleportBack, e);
            if (t.parentNode instanceof ShadowRoot)
                return Nt(t.parentNode.host, e);
            if (t.parentElement) return Nt(t.parentElement, e);
        }
    }
    function qp(t) {
        return Xf().some((e) => t.matches(e));
    }
    var ec = [];
    function kp(t) {
        ec.push(t);
    }
    var Ip = 1;
    function Ot(t, e = gr, r = () => {}) {
        Nt(t, (n) => n._x_ignore) ||
            Np(() => {
                e(t, (n, s) => {
                    n._x_marker ||
                        (r(n, s),
                        ec.forEach((i) => i(n, s)),
                        Ra(n, n.attributes).forEach((i) => i()),
                        n._x_ignore || (n._x_marker = Ip++),
                        n._x_ignore && s());
                });
            });
    }
    function sn(t, e = gr) {
        e(t, (r) => {
            (op(r), Lf(r), delete r._x_marker);
        });
    }
    function Pp() {
        [
            ["ui", "dialog", ["[x-dialog], [x-popover]"]],
            ["anchor", "anchor", ["[x-anchor]"]],
            ["sort", "sort", ["[x-sort]"]],
        ].forEach(([e, r, n]) => {
            Tp(r) ||
                n.some((s) => {
                    if (document.querySelector(s))
                        return (
                            bt(`found "${s}", but missing ${e} plugin`),
                            !0
                        );
                });
        });
    }
    var Fo = [],
        Ia = !1;
    function Pa(t = () => {}) {
        return (
            queueMicrotask(() => {
                Ia ||
                    setTimeout(() => {
                        Ho();
                    });
            }),
            new Promise((e) => {
                Fo.push(() => {
                    (t(), e());
                });
            })
        );
    }
    function Ho() {
        for (Ia = !1; Fo.length; ) Fo.shift()();
    }
    function Mp() {
        Ia = !0;
    }
    function Ma(t, e) {
        return Array.isArray(e)
            ? kl(t, e.join(" "))
            : typeof e == "object" && e !== null
              ? Bp(t, e)
              : typeof e == "function"
                ? Ma(t, e())
                : kl(t, e);
    }
    function zo(t) {
        return t.split(/\s/).filter(Boolean);
    }
    function kl(t, e) {
        let r = (s) =>
                zo(s)
                    .filter((i) => !t.classList.contains(i))
                    .filter(Boolean),
            n = (s) => (
                t.classList.add(...s),
                () => {
                    t.classList.remove(...s);
                }
            );
        return ((e = e === !0 ? (e = "") : e || ""), n(r(e)));
    }
    function Bp(t, e) {
        let r = Object.entries(e)
                .flatMap(([o, a]) => (a ? zo(o) : !1))
                .filter(Boolean),
            n = Object.entries(e)
                .flatMap(([o, a]) => (a ? !1 : zo(o)))
                .filter(Boolean),
            s = [],
            i = [];
        return (
            n.forEach((o) => {
                t.classList.contains(o) && (t.classList.remove(o), i.push(o));
            }),
            r.forEach((o) => {
                t.classList.contains(o) || (t.classList.add(o), s.push(o));
            }),
            () => {
                (i.forEach((o) => t.classList.add(o)),
                    s.forEach((o) => t.classList.remove(o)));
            }
        );
    }
    function ai(t, e) {
        return typeof e == "object" && e !== null ? $p(t, e) : Dp(t, e);
    }
    function $p(t, e) {
        let r = {};
        return (
            Object.entries(e).forEach(([n, s]) => {
                ((r[n] = t.style[n]),
                    n.startsWith("--") || (n = jp(n)),
                    t.style.setProperty(n, s));
            }),
            setTimeout(() => {
                t.style.length === 0 && t.removeAttribute("style");
            }),
            () => {
                ai(t, r);
            }
        );
    }
    function Dp(t, e) {
        let r = t.getAttribute("style", e);
        return (
            t.setAttribute("style", e),
            () => {
                t.setAttribute("style", r || "");
            }
        );
    }
    function jp(t) {
        return t.replace(/([a-z])([A-Z])/g, "$1-$2").toLowerCase();
    }
    function Ko(t, e = () => {}) {
        let r = !1;
        return function () {
            r ? e.apply(this, arguments) : ((r = !0), t.apply(this, arguments));
        };
    }
    Ee(
        "transition",
        (t, { value: e, modifiers: r, expression: n }, { evaluate: s }) => {
            (typeof n == "function" && (n = s(n)),
                n !== !1 &&
                    (!n || typeof n == "boolean" ? Fp(t, r, e) : Up(t, n, e)));
        },
    );
    function Up(t, e, r) {
        (tc(t, Ma, ""),
            {
                enter: (s) => {
                    t._x_transition.enter.during = s;
                },
                "enter-start": (s) => {
                    t._x_transition.enter.start = s;
                },
                "enter-end": (s) => {
                    t._x_transition.enter.end = s;
                },
                leave: (s) => {
                    t._x_transition.leave.during = s;
                },
                "leave-start": (s) => {
                    t._x_transition.leave.start = s;
                },
                "leave-end": (s) => {
                    t._x_transition.leave.end = s;
                },
            }[r](e));
    }
    function Fp(t, e, r) {
        tc(t, ai);
        let n = !e.includes("in") && !e.includes("out") && !r,
            s = n || e.includes("in") || ["enter"].includes(r),
            i = n || e.includes("out") || ["leave"].includes(r);
        (e.includes("in") &&
            !n &&
            (e = e.filter((y, S) => S < e.indexOf("out"))),
            e.includes("out") &&
                !n &&
                (e = e.filter((y, S) => S > e.indexOf("out"))));
        let o = !e.includes("opacity") && !e.includes("scale"),
            a = o || e.includes("opacity"),
            l = o || e.includes("scale"),
            f = a ? 0 : 1,
            u = l ? Sn(e, "scale", 95) / 100 : 1,
            d = Sn(e, "delay", 0) / 1e3,
            h = Sn(e, "origin", "center"),
            g = "opacity, transform",
            v = Sn(e, "duration", 150) / 1e3,
            b = Sn(e, "duration", 75) / 1e3,
            _ = "cubic-bezier(0.4, 0.0, 0.2, 1)";
        (s &&
            ((t._x_transition.enter.during = {
                transformOrigin: h,
                transitionDelay: `${d}s`,
                transitionProperty: g,
                transitionDuration: `${v}s`,
                transitionTimingFunction: _,
            }),
            (t._x_transition.enter.start = {
                opacity: f,
                transform: `scale(${u})`,
            }),
            (t._x_transition.enter.end = {
                opacity: 1,
                transform: "scale(1)",
            })),
            i &&
                ((t._x_transition.leave.during = {
                    transformOrigin: h,
                    transitionDelay: `${d}s`,
                    transitionProperty: g,
                    transitionDuration: `${b}s`,
                    transitionTimingFunction: _,
                }),
                (t._x_transition.leave.start = {
                    opacity: 1,
                    transform: "scale(1)",
                }),
                (t._x_transition.leave.end = {
                    opacity: f,
                    transform: `scale(${u})`,
                })));
    }
    function tc(t, e, r = {}) {
        t._x_transition ||
            (t._x_transition = {
                enter: { during: r, start: r, end: r },
                leave: { during: r, start: r, end: r },
                in(n = () => {}, s = () => {}) {
                    Wo(
                        t,
                        e,
                        {
                            during: this.enter.during,
                            start: this.enter.start,
                            end: this.enter.end,
                        },
                        n,
                        s,
                    );
                },
                out(n = () => {}, s = () => {}) {
                    Wo(
                        t,
                        e,
                        {
                            during: this.leave.during,
                            start: this.leave.start,
                            end: this.leave.end,
                        },
                        n,
                        s,
                    );
                },
            });
    }
    window.Element.prototype._x_toggleAndCascadeWithTransitions = function (
        t,
        e,
        r,
        n,
    ) {
        const s =
            document.visibilityState === "visible"
                ? requestAnimationFrame
                : setTimeout;
        let i = () => s(r);
        if (e) {
            t._x_transition && (t._x_transition.enter || t._x_transition.leave)
                ? t._x_transition.enter &&
                  (Object.entries(t._x_transition.enter.during).length ||
                      Object.entries(t._x_transition.enter.start).length ||
                      Object.entries(t._x_transition.enter.end).length)
                    ? t._x_transition.in(r)
                    : i()
                : t._x_transition
                  ? t._x_transition.in(r)
                  : i();
            return;
        }
        ((t._x_hidePromise = t._x_transition
            ? new Promise((o, a) => {
                  (t._x_transition.out(
                      () => {},
                      () => o(n),
                  ),
                      t._x_transitioning &&
                          t._x_transitioning.beforeCancel(() =>
                              a({ isFromCancelledTransition: !0 }),
                          ));
              })
            : Promise.resolve(n)),
            queueMicrotask(() => {
                let o = rc(t);
                o
                    ? (o._x_hideChildren || (o._x_hideChildren = []),
                      o._x_hideChildren.push(t))
                    : s(() => {
                          let a = (l) => {
                              let f = Promise.all([
                                  l._x_hidePromise,
                                  ...(l._x_hideChildren || []).map(a),
                              ]).then(([u]) => u?.());
                              return (
                                  delete l._x_hidePromise,
                                  delete l._x_hideChildren,
                                  f
                              );
                          };
                          a(t).catch((l) => {
                              if (!l.isFromCancelledTransition) throw l;
                          });
                      });
            }));
    };
    function rc(t) {
        let e = t.parentNode;
        if (e) return e._x_hidePromise ? e : rc(e);
    }
    function Wo(
        t,
        e,
        { during: r, start: n, end: s } = {},
        i = () => {},
        o = () => {},
    ) {
        if (
            (t._x_transitioning && t._x_transitioning.cancel(),
            Object.keys(r).length === 0 &&
                Object.keys(n).length === 0 &&
                Object.keys(s).length === 0)
        ) {
            (i(), o());
            return;
        }
        let a, l, f;
        Hp(t, {
            start() {
                a = e(t, n);
            },
            during() {
                l = e(t, r);
            },
            before: i,
            end() {
                (a(), (f = e(t, s)));
            },
            after: o,
            cleanup() {
                (l(), f());
            },
        });
    }
    function Hp(t, e) {
        let r,
            n,
            s,
            i = Ko(() => {
                be(() => {
                    ((r = !0),
                        n || e.before(),
                        s || (e.end(), Ho()),
                        e.after(),
                        t.isConnected && e.cleanup(),
                        delete t._x_transitioning);
                });
            });
        ((t._x_transitioning = {
            beforeCancels: [],
            beforeCancel(o) {
                this.beforeCancels.push(o);
            },
            cancel: Ko(function () {
                for (; this.beforeCancels.length; )
                    this.beforeCancels.shift()();
                i();
            }),
            finish: i,
        }),
            be(() => {
                (e.start(), e.during());
            }),
            Mp(),
            requestAnimationFrame(() => {
                if (r) return;
                let o =
                        Number(
                            getComputedStyle(t)
                                .transitionDuration.replace(/,.*/, "")
                                .replace("s", ""),
                        ) * 1e3,
                    a =
                        Number(
                            getComputedStyle(t)
                                .transitionDelay.replace(/,.*/, "")
                                .replace("s", ""),
                        ) * 1e3;
                (o === 0 &&
                    (o =
                        Number(
                            getComputedStyle(t).animationDuration.replace(
                                "s",
                                "",
                            ),
                        ) * 1e3),
                    be(() => {
                        e.before();
                    }),
                    (n = !0),
                    requestAnimationFrame(() => {
                        r ||
                            (be(() => {
                                e.end();
                            }),
                            Ho(),
                            setTimeout(t._x_transitioning.finish, o + a),
                            (s = !0));
                    }));
            }));
    }
    function Sn(t, e, r) {
        if (t.indexOf(e) === -1) return r;
        const n = t[t.indexOf(e) + 1];
        if (!n || (e === "scale" && isNaN(n))) return r;
        if (e === "duration" || e === "delay") {
            let s = n.match(/([0-9]+)ms/);
            if (s) return s[1];
        }
        return e === "origin" &&
            ["top", "right", "left", "center", "bottom"].includes(
                t[t.indexOf(e) + 2],
            )
            ? [n, t[t.indexOf(e) + 2]].join(" ")
            : n;
    }
    var Ft = !1;
    function Kt(t, e = () => {}) {
        return (...r) => (Ft ? e(...r) : t(...r));
    }
    function zp(t) {
        return (...e) => Ft && t(...e);
    }
    var nc = [];
    function li(t) {
        nc.push(t);
    }
    function Kp(t, e) {
        (nc.forEach((r) => r(t, e)),
            (Ft = !0),
            sc(() => {
                Ot(e, (r, n) => {
                    n(r, () => {});
                });
            }),
            (Ft = !1));
    }
    var Vo = !1;
    function Wp(t, e) {
        (e._x_dataStack || (e._x_dataStack = t._x_dataStack),
            (Ft = !0),
            (Vo = !0),
            sc(() => {
                Vp(e);
            }),
            (Ft = !1),
            (Vo = !1));
    }
    function Vp(t) {
        let e = !1;
        Ot(t, (n, s) => {
            gr(n, (i, o) => {
                if (e && qp(i)) return o();
                ((e = !0), s(i, o));
            });
        });
    }
    function sc(t) {
        let e = wr;
        (Rl((r, n) => {
            let s = e(r);
            return (rn(s), () => {});
        }),
            t(),
            Rl(e));
    }
    function ic(t, e, r, n = []) {
        switch (
            (t._x_bindings || (t._x_bindings = tn({})),
            (t._x_bindings[e] = r),
            (e = n.includes("camel") ? tg(e) : e),
            e)
        ) {
            case "value":
                Gp(t, r);
                break;
            case "style":
                Xp(t, r);
                break;
            case "class":
                Zp(t, r);
                break;
            case "selected":
            case "checked":
                Jp(t, e, r);
                break;
            default:
                oc(t, e, r);
                break;
        }
    }
    function Gp(t, e) {
        if (Ba(t)) t.attributes.value === void 0 && (t.value = e);
        else if (Zs(t))
            Number.isInteger(e)
                ? (t.value = e)
                : !Array.isArray(e) &&
                    typeof e != "boolean" &&
                    ![null, void 0].includes(e)
                  ? (t.value = String(e))
                  : Array.isArray(e)
                    ? (t.checked = e.some((r) => rg(r, t.value)))
                    : (t.checked = !!e);
        else if (t.tagName === "SELECT") eg(t, e);
        else {
            if (t.value === e) return;
            t.value = e === void 0 ? "" : e;
        }
    }
    function Zp(t, e) {
        (t._x_undoAddedClasses && t._x_undoAddedClasses(),
            (t._x_undoAddedClasses = Ma(t, e)));
    }
    function Xp(t, e) {
        (t._x_undoAddedStyles && t._x_undoAddedStyles(),
            (t._x_undoAddedStyles = ai(t, e)));
    }
    function Jp(t, e, r) {
        (oc(t, e, r), Qp(t, e, r));
    }
    function oc(t, e, r) {
        [null, void 0, !1].includes(r) && sg(e)
            ? t.removeAttribute(e)
            : (ac(e) && (r = e), Yp(t, e, r));
    }
    function Yp(t, e, r) {
        t.getAttribute(e) != r && t.setAttribute(e, r);
    }
    function Qp(t, e, r) {
        t[e] !== r && (t[e] = r);
    }
    function eg(t, e) {
        const r = [].concat(e).map((n) => n + "");
        Array.from(t.options).forEach((n) => {
            n.selected = r.includes(n.value);
        });
    }
    function tg(t) {
        return t.toLowerCase().replace(/-(\w)/g, (e, r) => r.toUpperCase());
    }
    function rg(t, e) {
        return t == e;
    }
    function Fs(t) {
        return [1, "1", "true", "on", "yes", !0].includes(t)
            ? !0
            : [0, "0", "false", "off", "no", !1].includes(t)
              ? !1
              : t
                ? !!t
                : null;
    }
    var ng = new Set([
        "allowfullscreen",
        "async",
        "autofocus",
        "autoplay",
        "checked",
        "controls",
        "default",
        "defer",
        "disabled",
        "formnovalidate",
        "inert",
        "ismap",
        "itemscope",
        "loop",
        "multiple",
        "muted",
        "nomodule",
        "novalidate",
        "open",
        "playsinline",
        "readonly",
        "required",
        "reversed",
        "selected",
        "shadowrootclonable",
        "shadowrootdelegatesfocus",
        "shadowrootserializable",
    ]);
    function ac(t) {
        return ng.has(t);
    }
    function sg(t) {
        return ![
            "aria-pressed",
            "aria-checked",
            "aria-expanded",
            "aria-selected",
        ].includes(t);
    }
    function ig(t, e, r) {
        return t._x_bindings && t._x_bindings[e] !== void 0
            ? t._x_bindings[e]
            : lc(t, e, r);
    }
    function og(t, e, r, n = !0) {
        if (t._x_bindings && t._x_bindings[e] !== void 0)
            return t._x_bindings[e];
        if (t._x_inlineBindings && t._x_inlineBindings[e] !== void 0) {
            let s = t._x_inlineBindings[e];
            return ((s.extract = n), Bf(() => lr(t, s.expression)));
        }
        return lc(t, e, r);
    }
    function lc(t, e, r) {
        let n = t.getAttribute(e);
        return n === null
            ? typeof r == "function"
                ? r()
                : r
            : n === ""
              ? !0
              : ac(e)
                ? !![e, "true"].includes(n)
                : n;
    }
    function Zs(t) {
        return (
            t.type === "checkbox" ||
            t.localName === "ui-checkbox" ||
            t.localName === "ui-switch"
        );
    }
    function Ba(t) {
        return t.type === "radio" || t.localName === "ui-radio";
    }
    function fc(t, e) {
        let r;
        return function () {
            const n = this,
                s = arguments,
                i = function () {
                    ((r = null), t.apply(n, s));
                };
            (clearTimeout(r), (r = setTimeout(i, e)));
        };
    }
    function cc(t, e) {
        let r;
        return function () {
            let n = this,
                s = arguments;
            r || (t.apply(n, s), (r = !0), setTimeout(() => (r = !1), e));
        };
    }
    function uc({ get: t, set: e }, { get: r, set: n }) {
        let s = !0,
            i,
            o = wr(() => {
                let a = t(),
                    l = r();
                if (s) (n(ho(a)), (s = !1));
                else {
                    let f = JSON.stringify(a),
                        u = JSON.stringify(l);
                    f !== i ? n(ho(a)) : f !== u && e(ho(l));
                }
                ((i = JSON.stringify(t())), JSON.stringify(r()));
            });
        return () => {
            rn(o);
        };
    }
    function ho(t) {
        return typeof t == "object" ? JSON.parse(JSON.stringify(t)) : t;
    }
    function ag(t) {
        (Array.isArray(t) ? t : [t]).forEach((r) => r(on));
    }
    var nr = {},
        Il = !1;
    function lg(t, e) {
        if ((Il || ((nr = tn(nr)), (Il = !0)), e === void 0)) return nr[t];
        ((nr[t] = e),
            Ca(nr[t]),
            typeof e == "object" &&
                e !== null &&
                e.hasOwnProperty("init") &&
                typeof e.init == "function" &&
                nr[t].init());
    }
    function fg() {
        return nr;
    }
    var hc = {};
    function cg(t, e) {
        let r = typeof e != "function" ? () => e : e;
        return t instanceof Element ? dc(t, r()) : ((hc[t] = r), () => {});
    }
    function ug(t) {
        return (
            Object.entries(hc).forEach(([e, r]) => {
                Object.defineProperty(t, e, {
                    get() {
                        return (...n) => r(...n);
                    },
                });
            }),
            t
        );
    }
    function dc(t, e, r) {
        let n = [];
        for (; n.length; ) n.pop()();
        let s = Object.entries(e).map(([o, a]) => ({ name: o, value: a })),
            i = jf(s);
        return (
            (s = s.map((o) =>
                i.find((a) => a.name === o.name)
                    ? { name: `x-bind:${o.name}`, value: `"${o.value}"` }
                    : o,
            )),
            Ra(t, s, r).map((o) => {
                (n.push(o.runCleanups), o());
            }),
            () => {
                for (; n.length; ) n.pop()();
            }
        );
    }
    var pc = {};
    function hg(t, e) {
        pc[t] = e;
    }
    function dg(t, e) {
        return (
            Object.entries(pc).forEach(([r, n]) => {
                Object.defineProperty(t, r, {
                    get() {
                        return (...s) => n.bind(e)(...s);
                    },
                    enumerable: !1,
                });
            }),
            t
        );
    }
    var pg = {
            get reactive() {
                return tn;
            },
            get release() {
                return rn;
            },
            get effect() {
                return wr;
            },
            get raw() {
                return xf;
            },
            get transaction() {
                return sp;
            },
            version: "3.15.12",
            flushAndStopDeferringMutations: fp,
            dontAutoEvaluateFunctions: Bf,
            disableEffectScheduling: tp,
            startObservingMutations: Ta,
            stopObservingMutations: Rf,
            setReactivityEngine: rp,
            onAttributeRemoved: Cf,
            onAttributesAdded: Of,
            closestDataStack: dr,
            skipDuringClone: Kt,
            onlyDuringClone: zp,
            addRootSelector: Yf,
            addInitSelector: Qf,
            setErrorHandler: gp,
            interceptClone: li,
            addScopeToNode: Qn,
            deferMutations: lp,
            mapAttributes: qa,
            evaluateLater: Me,
            interceptInit: kp,
            initInterceptors: Ca,
            injectMagics: jn,
            setEvaluator: bp,
            setRawEvaluator: yp,
            mergeProxies: pr,
            extractProp: og,
            findClosest: Nt,
            onElRemoved: xa,
            closestRoot: oi,
            destroyTree: sn,
            interceptor: If,
            transition: Wo,
            setStyles: ai,
            mutateDom: be,
            directive: Ee,
            entangle: uc,
            throttle: cc,
            debounce: fc,
            evaluate: lr,
            evaluateRaw: xp,
            initTree: Ot,
            nextTick: Pa,
            prefixed: nn,
            prefix: Sp,
            plugin: ag,
            magic: at,
            store: lg,
            start: Rp,
            clone: Wp,
            cloneNode: Kp,
            bound: ig,
            $data: qf,
            watch: Af,
            walk: gr,
            data: hg,
            bind: cg,
        },
        on = pg;
    function gg(t, e) {
        const r = Object.create(null),
            n = t.split(",");
        for (let s = 0; s < n.length; s++) r[n[s]] = !0;
        return (s) => !!r[s];
    }
    var mg = Object.freeze({}),
        bg = Object.prototype.hasOwnProperty,
        fi = (t, e) => bg.call(t, e),
        fr = Array.isArray,
        Mn = (t) => gc(t) === "[object Map]",
        yg = (t) => typeof t == "string",
        $a = (t) => typeof t == "symbol",
        ci = (t) => t !== null && typeof t == "object",
        vg = Object.prototype.toString,
        gc = (t) => vg.call(t),
        mc = (t) => gc(t).slice(8, -1),
        Da = (t) =>
            yg(t) && t !== "NaN" && t[0] !== "-" && "" + parseInt(t, 10) === t,
        wg = (t) => {
            const e = Object.create(null);
            return (r) => e[r] || (e[r] = t(r));
        },
        Eg = wg((t) => t.charAt(0).toUpperCase() + t.slice(1)),
        bc = (t, e) => t !== e && (t === t || e === e),
        Go = new WeakMap(),
        Tn = [],
        gt,
        cr = Symbol("iterate"),
        Zo = Symbol("Map key iterate");
    function _g(t) {
        return t && t._isEffect === !0;
    }
    function xg(t, e = mg) {
        _g(t) && (t = t.raw);
        const r = Tg(t, e);
        return (e.lazy || r(), r);
    }
    function Ag(t) {
        t.active &&
            (yc(t), t.options.onStop && t.options.onStop(), (t.active = !1));
    }
    var Sg = 0;
    function Tg(t, e) {
        const r = function () {
            if (!r.active) return t();
            if (!Tn.includes(r)) {
                yc(r);
                try {
                    return (Og(), Tn.push(r), (gt = r), t());
                } finally {
                    (Tn.pop(), vc(), (gt = Tn[Tn.length - 1]));
                }
            }
        };
        return (
            (r.id = Sg++),
            (r.allowRecurse = !!e.allowRecurse),
            (r._isEffect = !0),
            (r.active = !0),
            (r.raw = t),
            (r.deps = []),
            (r.options = e),
            r
        );
    }
    function yc(t) {
        const { deps: e } = t;
        if (e.length) {
            for (let r = 0; r < e.length; r++) e[r].delete(t);
            e.length = 0;
        }
    }
    var Xr = !0,
        ja = [];
    function Ng() {
        (ja.push(Xr), (Xr = !1));
    }
    function Og() {
        (ja.push(Xr), (Xr = !0));
    }
    function vc() {
        const t = ja.pop();
        Xr = t === void 0 ? !0 : t;
    }
    function st(t, e, r) {
        if (!Xr || gt === void 0) return;
        let n = Go.get(t);
        n || Go.set(t, (n = new Map()));
        let s = n.get(r);
        (s || n.set(r, (s = new Set())),
            s.has(gt) ||
                (s.add(gt),
                gt.deps.push(s),
                gt.options.onTrack &&
                    gt.options.onTrack({
                        effect: gt,
                        target: t,
                        type: e,
                        key: r,
                    })));
    }
    function Ht(t, e, r, n, s, i) {
        const o = Go.get(t);
        if (!o) return;
        const a = new Set(),
            l = (u) => {
                u &&
                    u.forEach((d) => {
                        (d !== gt || d.allowRecurse) && a.add(d);
                    });
            };
        if (e === "clear") o.forEach(l);
        else if (r === "length" && fr(t))
            o.forEach((u, d) => {
                (d === "length" || d >= n) && l(u);
            });
        else
            switch ((r !== void 0 && l(o.get(r)), e)) {
                case "add":
                    fr(t)
                        ? Da(r) && l(o.get("length"))
                        : (l(o.get(cr)), Mn(t) && l(o.get(Zo)));
                    break;
                case "delete":
                    fr(t) || (l(o.get(cr)), Mn(t) && l(o.get(Zo)));
                    break;
                case "set":
                    Mn(t) && l(o.get(cr));
                    break;
            }
        const f = (u) => {
            (u.options.onTrigger &&
                u.options.onTrigger({
                    effect: u,
                    target: t,
                    key: r,
                    type: e,
                    newValue: n,
                    oldValue: s,
                    oldTarget: i,
                }),
                u.options.scheduler ? u.options.scheduler(u) : u());
        };
        a.forEach(f);
    }
    var Cg = gg("__proto__,__v_isRef,__isVue"),
        wc = new Set(
            Object.getOwnPropertyNames(Symbol)
                .map((t) => Symbol[t])
                .filter($a),
        ),
        Lg = Ec(),
        Rg = Ec(!0),
        Pl = qg();
    function qg() {
        const t = {};
        return (
            ["includes", "indexOf", "lastIndexOf"].forEach((e) => {
                t[e] = function (...r) {
                    const n = ge(this);
                    for (let i = 0, o = this.length; i < o; i++)
                        st(n, "get", i + "");
                    const s = n[e](...r);
                    return s === -1 || s === !1 ? n[e](...r.map(ge)) : s;
                };
            }),
            ["push", "pop", "shift", "unshift", "splice"].forEach((e) => {
                t[e] = function (...r) {
                    Ng();
                    const n = ge(this)[e].apply(this, r);
                    return (vc(), n);
                };
            }),
            t
        );
    }
    function Ec(t = !1, e = !1) {
        return function (n, s, i) {
            if (s === "__v_isReactive") return !t;
            if (s === "__v_isReadonly") return t;
            if (
                s === "__v_raw" &&
                i === (t ? (e ? Wg : Sc) : e ? Kg : Ac).get(n)
            )
                return n;
            const o = fr(n);
            if (!t && o && fi(Pl, s)) return Reflect.get(Pl, s, i);
            const a = Reflect.get(n, s, i);
            return ($a(s) ? wc.has(s) : Cg(s)) || (t || st(n, "get", s), e)
                ? a
                : Xo(a)
                  ? !o || !Da(s)
                      ? a.value
                      : a
                  : ci(a)
                    ? t
                        ? Tc(a)
                        : za(a)
                    : a;
        };
    }
    var kg = Ig();
    function Ig(t = !1) {
        return function (r, n, s, i) {
            let o = r[n];
            if (!t && ((s = ge(s)), (o = ge(o)), !fr(r) && Xo(o) && !Xo(s)))
                return ((o.value = s), !0);
            const a = fr(r) && Da(n) ? Number(n) < r.length : fi(r, n),
                l = Reflect.set(r, n, s, i);
            return (
                r === ge(i) &&
                    (a
                        ? bc(s, o) && Ht(r, "set", n, s, o)
                        : Ht(r, "add", n, s)),
                l
            );
        };
    }
    function Pg(t, e) {
        const r = fi(t, e),
            n = t[e],
            s = Reflect.deleteProperty(t, e);
        return (s && r && Ht(t, "delete", e, void 0, n), s);
    }
    function Mg(t, e) {
        const r = Reflect.has(t, e);
        return ((!$a(e) || !wc.has(e)) && st(t, "has", e), r);
    }
    function Bg(t) {
        return (st(t, "iterate", fr(t) ? "length" : cr), Reflect.ownKeys(t));
    }
    var $g = { get: Lg, set: kg, deleteProperty: Pg, has: Mg, ownKeys: Bg },
        Dg = {
            get: Rg,
            set(t, e) {
                return (
                    console.warn(
                        `Set operation on key "${String(e)}" failed: target is readonly.`,
                        t,
                    ),
                    !0
                );
            },
            deleteProperty(t, e) {
                return (
                    console.warn(
                        `Delete operation on key "${String(e)}" failed: target is readonly.`,
                        t,
                    ),
                    !0
                );
            },
        },
        Ua = (t) => (ci(t) ? za(t) : t),
        Fa = (t) => (ci(t) ? Tc(t) : t),
        Ha = (t) => t,
        ui = (t) => Reflect.getPrototypeOf(t);
    function xs(t, e, r = !1, n = !1) {
        t = t.__v_raw;
        const s = ge(t),
            i = ge(e);
        (e !== i && !r && st(s, "get", e), !r && st(s, "get", i));
        const { has: o } = ui(s),
            a = n ? Ha : r ? Fa : Ua;
        if (o.call(s, e)) return a(t.get(e));
        if (o.call(s, i)) return a(t.get(i));
        t !== s && t.get(e);
    }
    function As(t, e = !1) {
        const r = this.__v_raw,
            n = ge(r),
            s = ge(t);
        return (
            t !== s && !e && st(n, "has", t),
            !e && st(n, "has", s),
            t === s ? r.has(t) : r.has(t) || r.has(s)
        );
    }
    function Ss(t, e = !1) {
        return (
            (t = t.__v_raw),
            !e && st(ge(t), "iterate", cr),
            Reflect.get(t, "size", t)
        );
    }
    function Ml(t) {
        t = ge(t);
        const e = ge(this);
        return (ui(e).has.call(e, t) || (e.add(t), Ht(e, "add", t, t)), this);
    }
    function Bl(t, e) {
        e = ge(e);
        const r = ge(this),
            { has: n, get: s } = ui(r);
        let i = n.call(r, t);
        i ? xc(r, n, t) : ((t = ge(t)), (i = n.call(r, t)));
        const o = s.call(r, t);
        return (
            r.set(t, e),
            i ? bc(e, o) && Ht(r, "set", t, e, o) : Ht(r, "add", t, e),
            this
        );
    }
    function $l(t) {
        const e = ge(this),
            { has: r, get: n } = ui(e);
        let s = r.call(e, t);
        s ? xc(e, r, t) : ((t = ge(t)), (s = r.call(e, t)));
        const i = n ? n.call(e, t) : void 0,
            o = e.delete(t);
        return (s && Ht(e, "delete", t, void 0, i), o);
    }
    function Dl() {
        const t = ge(this),
            e = t.size !== 0,
            r = Mn(t) ? new Map(t) : new Set(t),
            n = t.clear();
        return (e && Ht(t, "clear", void 0, void 0, r), n);
    }
    function Ts(t, e) {
        return function (n, s) {
            const i = this,
                o = i.__v_raw,
                a = ge(o),
                l = e ? Ha : t ? Fa : Ua;
            return (
                !t && st(a, "iterate", cr),
                o.forEach((f, u) => n.call(s, l(f), l(u), i))
            );
        };
    }
    function Ns(t, e, r) {
        return function (...n) {
            const s = this.__v_raw,
                i = ge(s),
                o = Mn(i),
                a = t === "entries" || (t === Symbol.iterator && o),
                l = t === "keys" && o,
                f = s[t](...n),
                u = r ? Ha : e ? Fa : Ua;
            return (
                !e && st(i, "iterate", l ? Zo : cr),
                {
                    next() {
                        const { value: d, done: h } = f.next();
                        return h
                            ? { value: d, done: h }
                            : { value: a ? [u(d[0]), u(d[1])] : u(d), done: h };
                    },
                    [Symbol.iterator]() {
                        return this;
                    },
                }
            );
        };
    }
    function Pt(t) {
        return function (...e) {
            {
                const r = e[0] ? `on key "${e[0]}" ` : "";
                console.warn(
                    `${Eg(t)} operation ${r}failed: target is readonly.`,
                    ge(this),
                );
            }
            return t === "delete" ? !1 : this;
        };
    }
    function jg() {
        const t = {
                get(i) {
                    return xs(this, i);
                },
                get size() {
                    return Ss(this);
                },
                has: As,
                add: Ml,
                set: Bl,
                delete: $l,
                clear: Dl,
                forEach: Ts(!1, !1),
            },
            e = {
                get(i) {
                    return xs(this, i, !1, !0);
                },
                get size() {
                    return Ss(this);
                },
                has: As,
                add: Ml,
                set: Bl,
                delete: $l,
                clear: Dl,
                forEach: Ts(!1, !0),
            },
            r = {
                get(i) {
                    return xs(this, i, !0);
                },
                get size() {
                    return Ss(this, !0);
                },
                has(i) {
                    return As.call(this, i, !0);
                },
                add: Pt("add"),
                set: Pt("set"),
                delete: Pt("delete"),
                clear: Pt("clear"),
                forEach: Ts(!0, !1),
            },
            n = {
                get(i) {
                    return xs(this, i, !0, !0);
                },
                get size() {
                    return Ss(this, !0);
                },
                has(i) {
                    return As.call(this, i, !0);
                },
                add: Pt("add"),
                set: Pt("set"),
                delete: Pt("delete"),
                clear: Pt("clear"),
                forEach: Ts(!0, !0),
            };
        return (
            ["keys", "values", "entries", Symbol.iterator].forEach((i) => {
                ((t[i] = Ns(i, !1, !1)),
                    (r[i] = Ns(i, !0, !1)),
                    (e[i] = Ns(i, !1, !0)),
                    (n[i] = Ns(i, !0, !0)));
            }),
            [t, r, e, n]
        );
    }
    var [Ug, Fg] = jg();
    function _c(t, e) {
        const r = t ? Fg : Ug;
        return (n, s, i) =>
            s === "__v_isReactive"
                ? !t
                : s === "__v_isReadonly"
                  ? t
                  : s === "__v_raw"
                    ? n
                    : Reflect.get(fi(r, s) && s in n ? r : n, s, i);
    }
    var Hg = { get: _c(!1) },
        zg = { get: _c(!0) };
    function xc(t, e, r) {
        const n = ge(r);
        if (n !== r && e.call(t, n)) {
            const s = mc(t);
            console.warn(
                `Reactive ${s} contains both the raw and reactive versions of the same object${s === "Map" ? " as keys" : ""}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`,
            );
        }
    }
    var Ac = new WeakMap(),
        Kg = new WeakMap(),
        Sc = new WeakMap(),
        Wg = new WeakMap();
    function Vg(t) {
        switch (t) {
            case "Object":
            case "Array":
                return 1;
            case "Map":
            case "Set":
            case "WeakMap":
            case "WeakSet":
                return 2;
            default:
                return 0;
        }
    }
    function Gg(t) {
        return t.__v_skip || !Object.isExtensible(t) ? 0 : Vg(mc(t));
    }
    function za(t) {
        return t && t.__v_isReadonly ? t : Nc(t, !1, $g, Hg, Ac);
    }
    function Tc(t) {
        return Nc(t, !0, Dg, zg, Sc);
    }
    function Nc(t, e, r, n, s) {
        if (!ci(t))
            return (
                console.warn(`value cannot be made reactive: ${String(t)}`),
                t
            );
        if (t.__v_raw && !(e && t.__v_isReactive)) return t;
        const i = s.get(t);
        if (i) return i;
        const o = Gg(t);
        if (o === 0) return t;
        const a = new Proxy(t, o === 2 ? n : r);
        return (s.set(t, a), a);
    }
    function ge(t) {
        return (t && ge(t.__v_raw)) || t;
    }
    function Xo(t) {
        return !!(t && t.__v_isRef === !0);
    }
    at("nextTick", () => Pa);
    at("dispatch", (t) => Pn.bind(Pn, t));
    at("watch", (t, { evaluateLater: e, cleanup: r }) => (n, s) => {
        let i = e(n),
            a = Af(() => {
                let l;
                return (i((f) => (l = f)), l);
            }, s);
        r(a);
    });
    at("store", fg);
    at("data", (t) => qf(t));
    at("root", (t) => oi(t));
    at(
        "refs",
        (t) => (
            t._x_refs_proxy || (t._x_refs_proxy = pr(Zg(t))),
            t._x_refs_proxy
        ),
    );
    function Zg(t) {
        let e = [];
        return (
            Nt(t, (r) => {
                r._x_refs && e.push(r._x_refs);
            }),
            e
        );
    }
    var po = {};
    function Oc(t) {
        return (po[t] || (po[t] = 0), ++po[t]);
    }
    function Xg(t, e) {
        return Nt(t, (r) => {
            if (r._x_ids && r._x_ids[e]) return !0;
        });
    }
    function Jg(t, e) {
        (t._x_ids || (t._x_ids = {}), t._x_ids[e] || (t._x_ids[e] = Oc(e)));
    }
    at("id", (t, { cleanup: e }) => (r, n = null) => {
        let s = `${r}${n ? `-${n}` : ""}`;
        return Yg(t, s, e, () => {
            let i = Xg(t, r),
                o = i ? i._x_ids[r] : Oc(r);
            return n ? `${r}-${o}-${n}` : `${r}-${o}`;
        });
    });
    li((t, e) => {
        t._x_id && (e._x_id = t._x_id);
    });
    function Yg(t, e, r, n) {
        if ((t._x_id || (t._x_id = {}), t._x_id[e])) return t._x_id[e];
        let s = n();
        return (
            (t._x_id[e] = s),
            r(() => {
                delete t._x_id[e];
            }),
            s
        );
    }
    at("el", (t) => t);
    Cc("Focus", "focus", "focus");
    Cc("Persist", "persist", "persist");
    function Cc(t, e, r) {
        at(e, (n) =>
            bt(
                `You can't use [$${e}] without first installing the "${t}" plugin here: https://alpinejs.dev/plugins/${r}`,
                n,
            ),
        );
    }
    Ee(
        "modelable",
        (t, { expression: e }, { effect: r, evaluateLater: n, cleanup: s }) => {
            let i = n(e),
                o = () => {
                    let u;
                    return (i((d) => (u = d)), u);
                },
                a = n(`${e} = __placeholder`),
                l = (u) => a(() => {}, { scope: { __placeholder: u } }),
                f = o();
            (l(f),
                queueMicrotask(() => {
                    if (!t._x_model) return;
                    t._x_removeModelListeners.default();
                    let u = t._x_model.get,
                        d = t._x_model.setWithModifiers,
                        h = uc(
                            {
                                get() {
                                    return u();
                                },
                                set(g) {
                                    d(g);
                                },
                            },
                            {
                                get() {
                                    return o();
                                },
                                set(g) {
                                    l(g);
                                },
                            },
                        );
                    s(h);
                }));
        },
    );
    Ee("teleport", (t, { modifiers: e, expression: r }, { cleanup: n }) => {
        t.tagName.toLowerCase() !== "template" &&
            bt("x-teleport can only be used on a <template> tag", t);
        let s = jl(r),
            i = t.content.cloneNode(!0).firstElementChild;
        ((t._x_teleport = i),
            (i._x_teleportBack = t),
            t.setAttribute("data-teleport-template", !0),
            i.setAttribute("data-teleport-target", !0),
            t._x_forwardEvents &&
                t._x_forwardEvents.forEach((a) => {
                    i.addEventListener(a, (l) => {
                        (l.stopPropagation(),
                            t.dispatchEvent(new l.constructor(l.type, l)));
                    });
                }),
            Qn(i, {}, t));
        let o = (a, l, f) => {
            f.includes("prepend")
                ? l.parentNode.insertBefore(a, l)
                : f.includes("append")
                  ? l.parentNode.insertBefore(a, l.nextSibling)
                  : l.appendChild(a);
        };
        (be(() => {
            Kt(() => {
                (o(i, s, e), Ot(i));
            })();
        }),
            (t._x_teleportPutBack = () => {
                let a = jl(r);
                be(() => {
                    o(t._x_teleport, a, e);
                });
            }),
            n(() =>
                be(() => {
                    (i.remove(), sn(i));
                }),
            ));
    });
    var Qg = document.createElement("div");
    function jl(t) {
        let e = Kt(
            () => document.querySelector(t),
            () => Qg,
        )();
        return (
            e || bt(`Cannot find x-teleport element for selector: "${t}"`),
            e
        );
    }
    var Lc = () => {};
    Lc.inline = (t, { modifiers: e }, { cleanup: r }) => {
        (e.includes("self") ? (t._x_ignoreSelf = !0) : (t._x_ignore = !0),
            r(() => {
                e.includes("self")
                    ? delete t._x_ignoreSelf
                    : delete t._x_ignore;
            }));
    };
    Ee("ignore", Lc);
    Ee(
        "effect",
        Kt((t, { expression: e }, { effect: r }) => {
            r(Me(t, e));
        }),
    );
    function $r(t, e, r, n) {
        let s = t,
            i = (l) => n(l),
            o = {},
            a = (l, f) => (u) => f(l, u);
        return (
            r.includes("dot") && (e = em(e)),
            r.includes("camel") && (e = tm(e)),
            r.includes("capture") && (o.capture = !0),
            r.includes("window") && (s = window),
            r.includes("document") && (s = document),
            r.includes("passive") &&
                (o.passive = r[r.indexOf("passive") + 1] !== "false"),
            (i = Rc(r, i)),
            r.includes("prevent") &&
                (i = a(i, (l, f) => {
                    (f.preventDefault(), l(f));
                })),
            r.includes("stop") &&
                (i = a(i, (l, f) => {
                    (f.stopPropagation(), l(f));
                })),
            r.includes("once") &&
                (i = a(i, (l, f) => {
                    (l(f), s.removeEventListener(e, i, o));
                })),
            (r.includes("away") || r.includes("outside")) &&
                ((s = document),
                (i = a(i, (l, f) => {
                    t.contains(f.target) ||
                        (f.target.isConnected !== !1 &&
                            ((t.offsetWidth < 1 && t.offsetHeight < 1) ||
                                (t._x_isShown !== !1 && l(f))));
                }))),
            r.includes("self") &&
                (i = a(i, (l, f) => {
                    f.target === t && l(f);
                })),
            e === "submit" &&
                (i = a(i, (l, f) => {
                    (f.target._x_pendingModelUpdates &&
                        f.target._x_pendingModelUpdates.forEach((u) => u()),
                        l(f));
                })),
            (nm(e) || qc(e)) &&
                (i = a(i, (l, f) => {
                    sm(f, r) || l(f);
                })),
            s.addEventListener(e, i, o),
            () => {
                s.removeEventListener(e, i, o);
            }
        );
    }
    function Rc(t, e) {
        if (t.includes("debounce")) {
            let r = t[t.indexOf("debounce") + 1] || "invalid-wait",
                n = Xs(r.split("ms")[0]) ? Number(r.split("ms")[0]) : 250;
            e = fc(e, n);
        }
        if (t.includes("throttle")) {
            let r = t[t.indexOf("throttle") + 1] || "invalid-wait",
                n = Xs(r.split("ms")[0]) ? Number(r.split("ms")[0]) : 250;
            e = cc(e, n);
        }
        return e;
    }
    function em(t) {
        return t.replace(/-/g, ".");
    }
    function tm(t) {
        return t.toLowerCase().replace(/-(\w)/g, (e, r) => r.toUpperCase());
    }
    function Xs(t) {
        return !Array.isArray(t) && !isNaN(t);
    }
    function rm(t) {
        return [" ", "_"].includes(t)
            ? t
            : t
                  .replace(/([a-z])([A-Z])/g, "$1-$2")
                  .replace(/[_\s]/, "-")
                  .toLowerCase();
    }
    function nm(t) {
        return ["keydown", "keyup"].includes(t);
    }
    function qc(t) {
        return ["contextmenu", "click", "mouse"].some((e) => t.includes(e));
    }
    function sm(t, e) {
        let r = e.filter(
            (i) =>
                ![
                    "window",
                    "document",
                    "prevent",
                    "stop",
                    "once",
                    "capture",
                    "self",
                    "away",
                    "outside",
                    "passive",
                    "preserve-scroll",
                    "blur",
                    "change",
                    "lazy",
                ].includes(i),
        );
        if (r.includes("debounce")) {
            let i = r.indexOf("debounce");
            r.splice(
                i,
                Xs((r[i + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1,
            );
        }
        if (r.includes("throttle")) {
            let i = r.indexOf("throttle");
            r.splice(
                i,
                Xs((r[i + 1] || "invalid-wait").split("ms")[0]) ? 2 : 1,
            );
        }
        if (r.length === 0 || (r.length === 1 && Ul(t.key).includes(r[0])))
            return !1;
        const s = ["ctrl", "shift", "alt", "meta", "cmd", "super"].filter((i) =>
            r.includes(i),
        );
        return (
            (r = r.filter((i) => !s.includes(i))),
            !(
                s.length > 0 &&
                s.filter(
                    (o) => (
                        (o === "cmd" || o === "super") && (o = "meta"),
                        t[`${o}Key`]
                    ),
                ).length === s.length &&
                (qc(t.type) || Ul(t.key).includes(r[0]))
            )
        );
    }
    function Ul(t) {
        if (!t) return [];
        t = rm(t);
        let e = {
            ctrl: "control",
            slash: "/",
            space: " ",
            spacebar: " ",
            cmd: "meta",
            esc: "escape",
            up: "arrow-up",
            down: "arrow-down",
            left: "arrow-left",
            right: "arrow-right",
            period: ".",
            comma: ",",
            equal: "=",
            minus: "-",
            underscore: "_",
        };
        return (
            (e[t] = t),
            Object.keys(e)
                .map((r) => {
                    if (e[r] === t) return r;
                })
                .filter((r) => r)
        );
    }
    Ee(
        "model",
        (t, { modifiers: e, expression: r }, { effect: n, cleanup: s }) => {
            let i = t;
            e.includes("parent") && (i = Nt(t, (b) => b !== t));
            let o = Me(i, r),
                a;
            typeof r == "string"
                ? (a = Me(i, `${r} = __placeholder`))
                : typeof r == "function" && typeof r() == "string"
                  ? (a = Me(i, `${r()} = __placeholder`))
                  : (a = () => {});
            let l = () => {
                    let b;
                    return (o((_) => (b = _)), Fl(b) ? b.get() : b);
                },
                f = (b) => {
                    let _;
                    (o((y) => (_ = y)),
                        Fl(_)
                            ? _.set(b)
                            : a(() => {}, { scope: { __placeholder: b } }));
                };
            typeof r == "string" &&
                t.type === "radio" &&
                be(() => {
                    t.hasAttribute("name") || t.setAttribute("name", r);
                });
            let u = e.includes("change") || e.includes("lazy"),
                d = e.includes("blur"),
                h = e.includes("enter"),
                g = u || d || h,
                v;
            if (Ft) v = () => {};
            else if (g) {
                let b = [],
                    _ = (y) => f(Os(t, e, y, l()));
                if (
                    (u && b.push($r(t, "change", e, _)),
                    d && (b.push($r(t, "blur", e, _)), t.form))
                ) {
                    let y = t.form,
                        S = () => _({ target: t });
                    (y._x_pendingModelUpdates ||
                        (y._x_pendingModelUpdates = []),
                        y._x_pendingModelUpdates.push(S),
                        s(() => {
                            y._x_pendingModelUpdates &&
                                y._x_pendingModelUpdates.splice(
                                    y._x_pendingModelUpdates.indexOf(S),
                                    1,
                                );
                        }));
                }
                (h &&
                    b.push(
                        $r(t, "keydown", e, (y) => {
                            y.key === "Enter" && _(y);
                        }),
                    ),
                    (v = () => b.forEach((y) => y())));
            } else {
                let b =
                    t.tagName.toLowerCase() === "select" ||
                    ["checkbox", "radio"].includes(t.type)
                        ? "change"
                        : "input";
                v = $r(t, b, e, (_) => {
                    f(Os(t, e, _, l()));
                });
            }
            if (
                (e.includes("fill") &&
                    ([void 0, null, ""].includes(l()) ||
                        (Zs(t) && Array.isArray(l())) ||
                        (t.tagName.toLowerCase() === "select" && t.multiple)) &&
                    f(Os(t, e, { target: t }, l())),
                t._x_removeModelListeners || (t._x_removeModelListeners = {}),
                (t._x_removeModelListeners.default = v),
                s(() => t._x_removeModelListeners.default()),
                t.form)
            ) {
                let b = $r(t.form, "reset", [], (_) => {
                    Pa(
                        () =>
                            t._x_model &&
                            t._x_model.set(Os(t, e, { target: t }, l())),
                    );
                });
                s(() => b());
            }
            ((t._x_model = {
                get() {
                    return l();
                },
                set(b) {
                    f(b);
                },
                setWithModifiers: Rc(e, f),
            }),
                (t._x_forceModelUpdate = (b) => {
                    (b === void 0 &&
                        typeof r == "string" &&
                        r.match(/\./) &&
                        (b = ""),
                        be(() => {
                            Zs(t)
                                ? Array.isArray(b)
                                    ? (t.checked = b.some((_) => _ == t.value))
                                    : (t.checked = !!b)
                                : Ba(t)
                                  ? typeof b == "boolean"
                                      ? (t.checked = Fs(t.value) === b)
                                      : (t.checked = t.value == b)
                                  : ic(t, "value", b);
                        }));
                }),
                n(() => {
                    let b = l();
                    (e.includes("unintrusive") &&
                        document.activeElement.isSameNode(t)) ||
                        t._x_forceModelUpdate(b);
                }));
        },
    );
    function Os(t, e, r, n) {
        return be(() => {
            if (r instanceof CustomEvent && r.detail !== void 0)
                return r.detail !== null && r.detail !== void 0
                    ? r.detail
                    : r.target.value;
            if (Zs(t))
                if (Array.isArray(n)) {
                    let s = null;
                    return (
                        e.includes("number")
                            ? (s = go(r.target.value))
                            : e.includes("boolean")
                              ? (s = Fs(r.target.value))
                              : (s = r.target.value),
                        r.target.checked
                            ? n.includes(s)
                                ? n
                                : n.concat([s])
                            : n.filter((i) => !im(i, s))
                    );
                } else return r.target.checked;
            else {
                if (t.tagName.toLowerCase() === "select" && t.multiple)
                    return e.includes("number")
                        ? Array.from(r.target.selectedOptions).map((s) => {
                              let i = s.value || s.text;
                              return go(i);
                          })
                        : e.includes("boolean")
                          ? Array.from(r.target.selectedOptions).map((s) => {
                                let i = s.value || s.text;
                                return Fs(i);
                            })
                          : Array.from(r.target.selectedOptions).map(
                                (s) => s.value || s.text,
                            );
                {
                    let s;
                    return (
                        Ba(t)
                            ? r.target.checked
                                ? (s = r.target.value)
                                : (s = n)
                            : (s = r.target.value),
                        e.includes("number")
                            ? go(s)
                            : e.includes("boolean")
                              ? Fs(s)
                              : e.includes("trim")
                                ? s.trim()
                                : s
                    );
                }
            }
        });
    }
    function go(t) {
        let e = t ? parseFloat(t) : null;
        return om(e) ? e : t;
    }
    function im(t, e) {
        return t == e;
    }
    function om(t) {
        return !Array.isArray(t) && !isNaN(t);
    }
    function Fl(t) {
        return (
            t !== null &&
            typeof t == "object" &&
            typeof t.get == "function" &&
            typeof t.set == "function"
        );
    }
    Ee("cloak", (t) =>
        queueMicrotask(() => be(() => t.removeAttribute(nn("cloak")))),
    );
    Qf(() => `[${nn("init")}]`);
    Ee(
        "init",
        Kt((t, { expression: e }, { evaluate: r }) =>
            typeof e == "string" ? !!e.trim() && r(e, {}, !1) : r(e, {}, !1),
        ),
    );
    Ee("text", (t, { expression: e }, { effect: r, evaluateLater: n }) => {
        let s = n(e);
        r(() => {
            s((i) => {
                be(() => {
                    t.textContent = i;
                });
            });
        });
    });
    Ee("html", (t, { expression: e }, { effect: r, evaluateLater: n }) => {
        let s = n(e);
        r(() => {
            s((i) => {
                be(() => {
                    ((t.innerHTML = i ?? ""),
                        (t._x_ignoreSelf = !0),
                        Ot(t),
                        delete t._x_ignoreSelf);
                });
            });
        });
    });
    qa(Hf(":", zf(nn("bind:"))));
    var kc = (
        t,
        { value: e, modifiers: r, expression: n, original: s },
        { effect: i, cleanup: o },
    ) => {
        if (!e) {
            let l = {};
            (ug(l),
                Me(t, n)(
                    (u) => {
                        dc(t, u, s);
                    },
                    { scope: l },
                ));
            return;
        }
        if (e === "key") return am(t, n);
        if (
            t._x_inlineBindings &&
            t._x_inlineBindings[e] &&
            t._x_inlineBindings[e].extract
        )
            return;
        let a = Me(t, n);
        (i(() =>
            a((l) => {
                (l === void 0 &&
                    typeof n == "string" &&
                    n.match(/\./) &&
                    (l = ""),
                    be(() => ic(t, e, l, r)));
            }),
        ),
            o(() => {
                (t._x_undoAddedClasses && t._x_undoAddedClasses(),
                    t._x_undoAddedStyles && t._x_undoAddedStyles());
            }));
    };
    kc.inline = (t, { value: e, modifiers: r, expression: n }) => {
        e &&
            (t._x_inlineBindings || (t._x_inlineBindings = {}),
            (t._x_inlineBindings[e] = { expression: n, extract: !1 }));
    };
    Ee("bind", kc);
    function am(t, e) {
        t._x_keyExpression = e;
    }
    Yf(() => `[${nn("data")}]`);
    Ee("data", (t, { expression: e }, { cleanup: r }) => {
        if (lm(t)) return;
        e = e === "" ? "{}" : e;
        let n = {};
        jn(n, t);
        let s = {};
        dg(s, n);
        let i = lr(t, e, { scope: s });
        ((i === void 0 || i === !0) && (i = {}), jn(i, t));
        let o = tn(i);
        Ca(o);
        let a = Qn(t, o);
        (o.init && lr(t, o.init),
            r(() => {
                (o.destroy && lr(t, o.destroy), a());
            }));
    });
    li((t, e) => {
        t._x_dataStack &&
            ((e._x_dataStack = t._x_dataStack),
            e.setAttribute("data-has-alpine-state", !0));
    });
    function lm(t) {
        return Ft ? (Vo ? !0 : t.hasAttribute("data-has-alpine-state")) : !1;
    }
    Ee("show", (t, { modifiers: e, expression: r }, { effect: n }) => {
        let s = Me(t, r);
        (t._x_doHide ||
            (t._x_doHide = () => {
                be(() => {
                    t.style.setProperty(
                        "display",
                        "none",
                        e.includes("important") ? "important" : void 0,
                    );
                });
            }),
            t._x_doShow ||
                (t._x_doShow = () => {
                    be(() => {
                        t.style.length === 1 && t.style.display === "none"
                            ? t.removeAttribute("style")
                            : t.style.removeProperty("display");
                    });
                }));
        let i = () => {
                (t._x_doHide(), (t._x_isShown = !1));
            },
            o = () => {
                (t._x_doShow(), (t._x_isShown = !0));
            },
            a = () => setTimeout(o),
            l = Ko(
                (d) => (d ? o() : i()),
                (d) => {
                    typeof t._x_toggleAndCascadeWithTransitions == "function"
                        ? t._x_toggleAndCascadeWithTransitions(t, d, o, i)
                        : d
                          ? a()
                          : i();
                },
            ),
            f,
            u = !0;
        n(() =>
            s((d) => {
                (!u && d === f) ||
                    (e.includes("immediate") && (d ? a() : i()),
                    l(d),
                    (f = d),
                    (u = !1));
            }),
        );
    });
    Ee("for", (t, { expression: e }, { effect: r, cleanup: n }) => {
        let s = um(e),
            i = Me(t, s.items),
            o = Me(t, t._x_keyExpression || "index");
        ((t._x_lookup = new Map()),
            r(() => cm(t, s, i, o)),
            n(() => {
                (t._x_lookup.forEach((a) =>
                    be(() => {
                        (sn(a), a.remove());
                    }),
                ),
                    delete t._x_lookup);
            }));
    });
    function fm(t) {
        return (e) => {
            Object.entries(e).forEach(([r, n]) => {
                t[r] = n;
            });
        };
    }
    function cm(t, e, r, n) {
        r((s) => {
            (dm(s) && (s = Array.from({ length: s }, (f, u) => u + 1)),
                s == null && (s = []),
                s instanceof Set && (s = Array.from(s)),
                s instanceof Map && (s = Array.from(s)));
            let i = t._x_lookup,
                o = new Map();
            t._x_lookup = o;
            let a = pm(s),
                l = Object.entries(s).map(([f, u]) => {
                    a || (f = parseInt(f));
                    let d = hm(e, u, f, s),
                        h;
                    return (
                        n(
                            (g) => {
                                (typeof g == "object" &&
                                    bt(
                                        "x-for key cannot be an object, it must be a string or an integer",
                                        t,
                                    ),
                                    i.has(g) &&
                                        (o.set(g, i.get(g)), i.delete(g)),
                                    (h = g));
                            },
                            { scope: { index: f, ...d } },
                        ),
                        [h, d]
                    );
                });
            be(() => {
                i.forEach((d) => {
                    (sn(d), d.remove());
                });
                let f = new Set(),
                    u = t;
                (l.forEach(([d, h]) => {
                    if (o.has(d)) {
                        let b = o.get(d);
                        (b._x_refreshXForScope(h),
                            u.nextElementSibling !== b &&
                                (u.nextElementSibling &&
                                    b.replaceWith(u.nextElementSibling),
                                u.after(b)),
                            (u = b),
                            b._x_currentIfEl &&
                                (b.nextElementSibling !== b._x_currentIfEl &&
                                    u.after(b._x_currentIfEl),
                                (u = b._x_currentIfEl)));
                        return;
                    }
                    t.content.children.length > 1 &&
                        bt(
                            "x-for templates require a single root element, additional elements will be ignored.",
                            t,
                        );
                    let g = document.importNode(
                            t.content,
                            !0,
                        ).firstElementChild,
                        v = tn(h);
                    (Qn(g, v, t),
                        (g._x_refreshXForScope = fm(v)),
                        o.set(d, g),
                        f.add(g),
                        u.after(g),
                        (u = g));
                }),
                    Kt(() => f.forEach((d) => Ot(d)))());
            });
        });
    }
    function um(t) {
        let e = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/,
            r = /^\s*\(|\)\s*$/g,
            n = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/,
            s = t.match(n);
        if (!s) return;
        let i = {};
        i.items = s[2].trim();
        let o = s[1].replace(r, "").trim(),
            a = o.match(e);
        return (
            a
                ? ((i.item = o.replace(e, "").trim()),
                  (i.index = a[1].trim()),
                  a[2] && (i.collection = a[2].trim()))
                : (i.item = o),
            i
        );
    }
    function hm(t, e, r, n) {
        let s = {};
        return (
            /^\[.*\]$/.test(t.item) && Array.isArray(e)
                ? t.item
                      .replace("[", "")
                      .replace("]", "")
                      .split(",")
                      .map((o) => o.trim())
                      .forEach((o, a) => {
                          s[o] = e[a];
                      })
                : /^\{.*\}$/.test(t.item) &&
                    !Array.isArray(e) &&
                    typeof e == "object"
                  ? t.item
                        .replace("{", "")
                        .replace("}", "")
                        .split(",")
                        .map((o) => o.trim())
                        .forEach((o) => {
                            s[o] = e[o];
                        })
                  : (s[t.item] = e),
            t.index && (s[t.index] = r),
            t.collection && (s[t.collection] = n),
            s
        );
    }
    function dm(t) {
        return typeof t != "object" && !isNaN(t);
    }
    function pm(t) {
        return typeof t == "object" && !Array.isArray(t);
    }
    function Ic() {}
    Ic.inline = (t, { expression: e }, { cleanup: r }) => {
        let n = oi(t);
        n &&
            (n._x_refs || (n._x_refs = {}),
            (n._x_refs[e] = t),
            r(() => delete n._x_refs[e]));
    };
    Ee("ref", Ic);
    Ee("if", (t, { expression: e }, { effect: r, cleanup: n }) => {
        t.tagName.toLowerCase() !== "template" &&
            bt("x-if can only be used on a <template> tag", t);
        let s = Me(t, e),
            i = () => {
                if (t._x_currentIfEl) return t._x_currentIfEl;
                let a = t.content.cloneNode(!0).firstElementChild;
                return (
                    Qn(a, {}, t),
                    be(() => {
                        (t.after(a), Kt(() => Ot(a))());
                    }),
                    (t._x_currentIfEl = a),
                    (t._x_undoIf = () => {
                        (be(() => {
                            (sn(a), a.remove());
                        }),
                            delete t._x_currentIfEl);
                    }),
                    a
                );
            },
            o = () => {
                t._x_undoIf && (t._x_undoIf(), delete t._x_undoIf);
            };
        (r(() =>
            s((a) => {
                a ? i() : o();
            }),
        ),
            n(() => t._x_undoIf && t._x_undoIf()));
    });
    Ee("id", (t, { expression: e }, { evaluate: r }) => {
        r(e).forEach((s) => Jg(t, s));
    });
    li((t, e) => {
        t._x_ids && (e._x_ids = t._x_ids);
    });
    qa(Hf("@", zf(nn("on:"))));
    Ee(
        "on",
        Kt((t, { value: e, modifiers: r, expression: n }, { cleanup: s }) => {
            let i = n ? Me(t, n) : () => {};
            t.tagName.toLowerCase() === "template" &&
                (t._x_forwardEvents || (t._x_forwardEvents = []),
                t._x_forwardEvents.includes(e) || t._x_forwardEvents.push(e));
            let o = $r(t, e, r, (a) => {
                i(() => {}, { scope: { $event: a }, params: [a] });
            });
            s(() => o());
        }),
    );
    hi("Collapse", "collapse", "collapse");
    hi("Intersect", "intersect", "intersect");
    hi("Focus", "trap", "focus");
    hi("Mask", "mask", "mask");
    function hi(t, e, r) {
        Ee(e, (n) =>
            bt(
                `You can't use [x-${e}] without first installing the "${t}" plugin here: https://alpinejs.dev/plugins/${r}`,
                n,
            ),
        );
    }
    on.setEvaluator(vp);
    on.setRawEvaluator(Ap);
    on.setReactivityEngine({ reactive: za, effect: xg, release: Ag, raw: ge });
    var gm = on,
        we = gm;
    const mm = (t) => {
            const e =
                t <= 5 ? 1 : t <= 10 ? 2 : t <= 25 ? 5 : t <= 50 ? 10 : 20;
            return Math.ceil(t / e) * e;
        },
        bm = (t) =>
            String(t).replace(
                /[&<>"']/g,
                (e) =>
                    ({
                        "&": "&amp;",
                        "<": "&lt;",
                        ">": "&gt;",
                        '"': "&quot;",
                        "'": "&#39;",
                    })[e],
            );
    function ym(t) {
        t.data("areaChart", ({ labels: e, series: r, order: n }) => ({
            labels: e,
            series: r,
            key: n[0],
            hover: null,
            width: 640,
            height: 280,
            pad: { top: 18, right: 18, bottom: 34, left: 38 },
            init() {
                new ResizeObserver(([s]) => {
                    ((this.width = Math.max(
                        260,
                        Math.round(s.contentRect.width),
                    )),
                        (this.height = Math.max(
                            220,
                            Math.round(s.contentRect.height),
                        )));
                }).observe(this.$refs.frame);
            },
            get values() {
                return this.series[this.key].values;
            },
            get ceiling() {
                return mm(Math.max(...this.values, 1));
            },
            get ticks() {
                const s = this.ceiling <= 4 ? 1 : Math.ceil(this.ceiling / 4),
                    i = [];
                for (let o = 0; o <= this.ceiling; o += s)
                    i.push({ value: o, y: this.y(o) });
                return i;
            },
            get points() {
                return this.values.map((s, i) => ({
                    x: this.x(i),
                    y: this.y(s),
                    value: s,
                    label: this.labels[i],
                }));
            },
            get gridMarkup() {
                const { left: s } = this.pad,
                    i = this.width - this.pad.right,
                    o = this.ticks.map(
                        (l) =>
                            `<line class="nw-chart__grid" x1="${s}" x2="${i}" y1="${l.y}" y2="${l.y}"/><text class="nw-chart__tick" x="${s - 10}" y="${l.y + 4}" text-anchor="end">${l.value}</text>`,
                    ),
                    a = this.labels.map(
                        (l, f) =>
                            `<text class="nw-chart__tick" x="${this.x(f)}" y="${this.height - 10}" text-anchor="middle">${bm(l)}</text>`,
                    );
                return o.join("") + a.join("");
            },
            get line() {
                const s = this.points;
                if (s.length < 2) return "";
                const i = this.y(0),
                    o = this.pad.top;
                let a = `M${s[0].x},${s[0].y}`;
                for (let l = 0; l < s.length - 1; l++) {
                    const f = s[l - 1] ?? s[l],
                        u = s[l + 2] ?? s[l + 1],
                        d = s[l].x + (s[l + 1].x - f.x) / 6,
                        h = Math.min(
                            i,
                            Math.max(o, s[l].y + (s[l + 1].y - f.y) / 6),
                        ),
                        g = s[l + 1].x - (u.x - s[l].x) / 6,
                        v = Math.min(
                            i,
                            Math.max(o, s[l + 1].y - (u.y - s[l].y) / 6),
                        );
                    a += ` C${d},${h} ${g},${v} ${s[l + 1].x},${s[l + 1].y}`;
                }
                return a;
            },
            get area() {
                const s = this.points;
                return s.length < 2
                    ? ""
                    : `${this.line} L${s[s.length - 1].x},${this.y(0)} L${s[0].x},${this.y(0)} Z`;
            },
            get active() {
                return this.hover === null ? null : this.points[this.hover];
            },
            x(s) {
                return (
                    this.pad.left +
                    (s / Math.max(this.values.length - 1, 1)) *
                        (this.width - this.pad.left - this.pad.right)
                );
            },
            y(s) {
                return (
                    this.pad.top +
                    (1 - s / this.ceiling) *
                        (this.height - this.pad.top - this.pad.bottom)
                );
            },
            total(s) {
                return this.series[s].values.reduce((i, o) => i + o, 0);
            },
            move(s) {
                const i = this.$refs.frame.getBoundingClientRect(),
                    o = s.clientX - i.left;
                let a = 0;
                (this.points.forEach((l, f) => {
                    Math.abs(l.x - o) < Math.abs(this.points[a].x - o) &&
                        (a = f);
                }),
                    (this.hover = a));
            },
            leave() {
                this.hover = null;
            },
        }));
    }
    function vm() {
        return {
            ready: !1,
            step: 0,
            steps: [],
            review: [],
            initializeWizard() {
                ((this.steps = [
                    ...this.$refs.form.querySelectorAll("[data-wizard-step]"),
                ].map((t, e) => ({
                    index: e,
                    title: t.dataset.stepTitle || `Step ${e + 1}`,
                    description: t.dataset.stepDescription || "",
                }))),
                    (this.ready = !0));
            },
            isLast() {
                return this.step === this.steps.length - 1;
            },
            completionPercentage() {
                return this.steps.length > 1
                    ? Math.round((this.step / (this.steps.length - 1)) * 100)
                    : 0;
            },
            panels() {
                return [
                    ...this.$refs.form.querySelectorAll("[data-wizard-step]"),
                ];
            },
            stepFields(t) {
                return [
                    ...(this.panels()[t]?.querySelectorAll("[data-field]") ||
                        []),
                ];
            },
            stepBusy() {
                return !!(
                    this.panels()[this.step]?.querySelector(
                        "[data-location-field]",
                    ) &&
                    (this.loadingStates || this.loadingCities)
                );
            },
            next() {
                return this.submitting || this.isLastStep || this.stepBusy()
                    ? !1
                    : this.validate(this.step)
                      ? (this.moveTo(this.step + 1), !0)
                      : (this.focusErrors(), !1);
            },
            back() {
                !this.submitting && this.step > 0 && this.moveTo(this.step - 1);
            },
            editStep(t) {
                !this.submitting &&
                    Number.isInteger(t) &&
                    t >= 0 &&
                    t < this.step &&
                    this.moveTo(t);
            },
            moveTo(t, e = !0) {
                ((this.step = Math.max(0, Math.min(t, this.steps.length - 1))),
                    this.isLastStep && (this.review = this.buildReview()),
                    e &&
                        this.$nextTick(() =>
                            this.panels()
                                [
                                    this.step
                                ]?.querySelector("[data-wizard-heading]")
                                ?.focus(),
                        ));
            },
            buildReview() {
                return this.steps
                    .slice(0, -1)
                    .map((t) => ({
                        title: t.title,
                        step: t.index,
                        entries: this.stepFields(t.index)
                            .map((e) => {
                                const r = [
                                    ...e.querySelectorAll(
                                        "input, select, textarea",
                                    ),
                                ].filter(
                                    (i) => !i.disabled && i.type !== "hidden",
                                );
                                if (!r.length) return null;
                                const n = this.controlFor?.(e) || r[0];
                                let s;
                                return (
                                    n.hasAttribute?.("data-phone-input")
                                        ? (s = n.dataset.e164 || n.value)
                                        : n.type === "file"
                                          ? (s = [...n.files]
                                                .map((i) => i.name)
                                                .join(", "))
                                          : ["radio", "checkbox"].includes(
                                                  n.type,
                                              )
                                            ? (s = r
                                                  .filter((i) => i.checked)
                                                  .map(
                                                      (i) =>
                                                          i
                                                              .closest("label")
                                                              ?.textContent.trim() ||
                                                          i.value,
                                                  )
                                                  .join(", "))
                                            : n.tagName === "SELECT"
                                              ? (s = n.value
                                                    ? n.selectedOptions[0]?.textContent.trim()
                                                    : "")
                                              : (s = n.value.trim()),
                                    s
                                        ? {
                                              label: e.dataset.label || n.name,
                                              value:
                                                  s.length > 1e3
                                                      ? `${s.slice(0, 1e3)}…`
                                                      : s,
                                          }
                                        : null
                                );
                            })
                            .filter(Boolean),
                    }))
                    .filter((t) => t.entries.length);
            },
        };
    }
    const Mt = (t) => (typeof t == "string" ? t : ""),
        Nn = (t) => {
            const e = new Set();
            return (Array.isArray(t) ? t : []).flatMap((r) =>
                !r || typeof r.value != "string" || !r.value || e.has(r.value)
                    ? []
                    : (e.add(r.value),
                      [
                          {
                              value: r.value,
                              label:
                                  typeof r.label == "string"
                                      ? r.label
                                      : r.value,
                          },
                      ]),
            );
        },
        Hl = (t, e) => {
            if (!t) throw new Error("A location lookup URL is required.");
            const r = t.split("#")[0];
            return r + (r.includes("?") ? "&" : "?") + new URLSearchParams(e);
        };
    function Pc(t = {}) {
        let e = null,
            r = null,
            n = 0,
            s = 0,
            i = !1,
            o = Mt(t.countryCode).trim().toUpperCase(),
            a = Mt(t.state);
        const l = t.nigeriaUsesLga !== !1,
            f = Mt(t.city),
            u = Mt(t.lga),
            d = () => {
                ((n += 1), e?.abort(), (e = null));
            },
            h = () => {
                ((s += 1), r?.abort(), (r = null));
            },
            g = (v) => {
                v.locationError = [v.statesError, v.citiesError]
                    .filter(Boolean)
                    .join(" ");
            };
        return {
            countries: Nn(t.countries),
            countryCode: o,
            state: a,
            city: o === "NG" && l ? "" : f || (o === "NG" ? u : ""),
            lga: o === "NG" && l ? u || f : "",
            states: Nn(t.preloadedStates ?? t.states),
            cities: Nn(t.preloadedCities ?? t.cities),
            loadingStates: !1,
            loadingCities: !1,
            stateManual: !1,
            cityManual: !1,
            locationError: "",
            statesError: "",
            citiesError: "",
            countryName() {
                return (
                    this.countries.find((v) => v.value === this.countryCode)
                        ?.label ?? ""
                );
            },
            async initLocations() {
                if (i) return !1;
                const v = this.countryCode,
                    b = this.state,
                    _ = await this.loadStates(!0);
                return i || this.countryCode !== v || this.state !== b
                    ? !1
                    : this.state
                      ? (await this.loadCities(!0)) && _
                      : _;
            },
            async loadStates(v = !1) {
                if (i) return !1;
                const b = this.countryCode,
                    _ = v && b === o;
                d();
                const y = n;
                if (
                    ((o = b),
                    (this.statesError = ""),
                    (!_ || !b) &&
                        (h(),
                        (a = ""),
                        (this.state = ""),
                        (this.city = ""),
                        (this.lga = ""),
                        (this.states = []),
                        (this.cities = []),
                        (this.stateManual = !1),
                        (this.cityManual = !1),
                        (this.citiesError = ""),
                        (this.loadingCities = !1)),
                    g(this),
                    !b)
                )
                    return ((this.loadingStates = !1), !1);
                const S = new AbortController();
                ((e = S), (this.loadingStates = !0));
                const N = () => !i && y === n && this.countryCode === b;
                try {
                    const P = await fetch(Hl(Mt(t.statesUrl), { country: b }), {
                        headers: { Accept: "application/json" },
                        credentials: "same-origin",
                        signal: S.signal,
                    });
                    if (!P.ok) throw new Error("The state lookup failed.");
                    const q = await P.json();
                    if (!q || !Array.isArray(q.data))
                        throw new Error("Invalid state lookup response.");
                    return !N() || S.signal.aborted
                        ? !1
                        : ((this.states = Nn(q.data)),
                          (this.stateManual = q.meta?.manual === !0),
                          !0);
                } catch (P) {
                    return (
                        N() &&
                            !S.signal.aborted &&
                            P?.name !== "AbortError" &&
                            ((this.statesError =
                                Mt(t.statesErrorMessage) ||
                                "We could not load the states. Please try again."),
                            g(this)),
                        !1
                    );
                } finally {
                    N() && ((this.loadingStates = !1), (e = null));
                }
            },
            async loadCities(v = !1) {
                if (i) return !1;
                const b = this.countryCode,
                    _ = this.state,
                    y = v && b === o && _ === a;
                h();
                const S = s;
                if (
                    ((a = _),
                    (this.citiesError = ""),
                    (!y || !b || !_) &&
                        ((this.city = ""),
                        (this.lga = ""),
                        (this.cities = []),
                        (this.cityManual = !1)),
                    g(this),
                    !b || !_)
                )
                    return ((this.loadingCities = !1), !1);
                const N = new AbortController();
                ((r = N), (this.loadingCities = !0));
                const P = () =>
                    !i && S === s && this.countryCode === b && this.state === _;
                try {
                    const q = await fetch(
                        Hl(Mt(t.citiesUrl), { country: b, state: _ }),
                        {
                            headers: { Accept: "application/json" },
                            credentials: "same-origin",
                            signal: N.signal,
                        },
                    );
                    if (!q.ok) throw new Error("The city lookup failed.");
                    const F = await q.json();
                    if (!F || !Array.isArray(F.data))
                        throw new Error("Invalid city lookup response.");
                    return !P() || N.signal.aborted
                        ? !1
                        : ((this.cities = Nn(F.data)),
                          (this.cityManual = F.meta?.manual === !0),
                          !0);
                } catch (q) {
                    return (
                        P() &&
                            !N.signal.aborted &&
                            q?.name !== "AbortError" &&
                            ((this.citiesError =
                                Mt(t.citiesErrorMessage) ||
                                "We could not load the cities. Please try again."),
                            g(this)),
                        !1
                    );
                } finally {
                    P() && ((this.loadingCities = !1), (r = null));
                }
            },
            destroyLocations() {
                ((i = !0),
                    d(),
                    h(),
                    (this.loadingStates = !1),
                    (this.loadingCities = !1));
            },
        };
    }
    function wm(t) {
        t.data("locationSelector", (e) => ({
            ...Pc(e),
            init() {
                return this.initLocations();
            },
            destroy() {
                this.destroyLocations();
            },
        }));
    }
    const mo = (t) =>
            t >= 1048576
                ? `${(t / 1048576).toFixed(1)} MB`
                : `${Math.max(1, Math.round(t / 1024))} KB`,
        Em = (t) => t.replace(/\[\]$/, "").replace(/\[([^\]]+)\]/g, ".$1");
    function zl({
        preview: t = !0,
        errors: e = {},
        mode: r = "workable",
        location: n = {},
        documents: s = [{ key: 0, name: "" }],
        profileDisabilityStatus: i = "",
        profileEmploymentCurrent: o = !1,
    } = {}) {
        return {
            ...vm(),
            ...(r === "native" ? Pc(n) : {}),
            preview: t,
            mode: r,
            errors: {},
            serverErrors: {},
            fileProblems: {},
            documents: s,
            profileDisabilityStatus: i,
            profileEmploymentCurrent: o,
            nextDocument: s.length,
            unmappedErrors: [],
            summary: [],
            files: {},
            over: null,
            submitting: !1,
            submissionError: "",
            done: !1,
            get isLastStep() {
                return this.isLast();
            },
            get progress() {
                return this.completionPercentage();
            },
            init() {
                this.$nextTick(() => {
                    (this.initializeWizard(),
                        this.restoreErrors(e),
                        this.mode === "native" && this.initLocations());
                });
            },
            destroy() {
                this.mode === "native" && this.destroyLocations();
            },
            fields() {
                return [...this.$refs.form.querySelectorAll("[data-field]")];
            },
            controlFor(a) {
                const l = [
                    ...a.querySelectorAll("input, select, textarea"),
                ].filter(
                    (f) =>
                        !f.disabled &&
                        f.type !== "hidden" &&
                        !f.hasAttribute?.("data-phone-search"),
                );
                return (
                    l.find((f) => f.hasAttribute?.("data-phone-input")) || l[0]
                );
            },
            error(a) {
                return this.errors[a] ?? "";
            },
            clear(a) {
                (delete this.errors[a],
                    delete this.serverErrors[a],
                    (this.summary = this.summary.filter((l) => l.name !== a)));
            },
            handleInput(a) {
                if (a.target.hasAttribute?.("data-phone-search")) return;
                const l = a.target.closest("[data-field]");
                if (!(!l || a.target.type === "file")) {
                    if (
                        (this.clear(l.dataset.field),
                        this.mode === "native" &&
                            l.dataset.field === "country_code")
                    )
                        ["state", "city", "local_government_area"].forEach(
                            (f) => this.clear(f),
                        );
                    else if (
                        this.mode === "native" &&
                        l.dataset.field === "state"
                    )
                        ["city", "local_government_area"].forEach((f) =>
                            this.clear(f),
                        );
                    else if (
                        this.mode === "native" &&
                        l.dataset.field ===
                            "profile_contact.disability_status" &&
                        a.target.value === "no"
                    ) {
                        this.clear("profile_contact.disability_details");
                        const f = this.$refs.form.querySelector(
                            '[data-field="profile_contact.disability_details"] input',
                        );
                        f && (f.value = "");
                    } else if (
                        this.mode === "native" &&
                        l.dataset.field === "profile_employment.is_current" &&
                        a.target.checked
                    ) {
                        const f = this.$refs.form.querySelector(
                            '[data-field="profile_employment.end_date"] input',
                        );
                        (f && (f.value = ""),
                            this.clear("profile_employment.end_date"));
                    }
                }
            },
            addDocument() {
                this.documents.length < 10 &&
                    this.documents.push({ key: this.nextDocument++, name: "" });
            },
            removeDocument(a) {
                this.documents.length < 2 ||
                    (this.documents.splice(a, 1),
                    Object.keys(this.errors)
                        .filter((l) => l.startsWith("education_documents"))
                        .forEach((l) => this.clear(l)),
                    (this.fileProblems = Object.fromEntries(
                        Object.entries(this.fileProblems).filter(
                            ([l]) => !l.startsWith("education_documents"),
                        ),
                    )),
                    (this.files = Object.fromEntries(
                        Object.entries(this.files).filter(
                            ([l]) => !l.startsWith("education_documents"),
                        ),
                    )));
            },
            restoreErrors(a) {
                ((this.errors = {}),
                    (this.serverErrors = {}),
                    (this.unmappedErrors = []),
                    Object.entries(a || {}).forEach(([l, f]) => {
                        const u = Array.isArray(f) ? f[0] : f;
                        if (typeof u != "string") return;
                        const d =
                                this.mode === "workable"
                                    ? l
                                          .replace(
                                              /^first_name(?=\.|$)/,
                                              "firstname",
                                          )
                                          .replace(
                                              /^last_name(?=\.|$)/,
                                              "lastname",
                                          )
                                          .replace(/^question_/, "answers.")
                                    : l,
                            h = this.fields().find((g) =>
                                [
                                    Em(g.dataset.field),
                                    g.dataset.errorKey,
                                    ...(g.dataset.type === "tel"
                                        ? [g.dataset.field + "_country"]
                                        : []),
                                ]
                                    .filter(Boolean)
                                    .some((b) =>
                                        [l, d].some(
                                            (_) =>
                                                _ === b ||
                                                _.startsWith(`${b}.`) ||
                                                b.startsWith(`${_}.`),
                                        ),
                                    ),
                            );
                        h
                            ? ((this.errors[h.dataset.field] = u),
                              (this.serverErrors[h.dataset.field] = u))
                            : this.unmappedErrors.push({
                                  name: l,
                                  label: "Application",
                                  message: u,
                              });
                    }),
                    (this.summary = this.listErrors()),
                    this.summary.length &&
                        (this.openFirstError(), this.focusErrors()));
            },
            listErrors() {
                return this.fields()
                    .filter((a) => this.errors[a.dataset.field])
                    .map((a) => ({
                        name: a.dataset.field,
                        label: a.dataset.label,
                        message: this.errors[a.dataset.field],
                        target: this.controlFor(a)?.id,
                        step: this.panels().indexOf(
                            a.closest("[data-wizard-step]"),
                        ),
                    }))
                    .concat(this.unmappedErrors);
            },
            problemWith(a) {
                const l = a.dataset.field,
                    f = a.dataset.type,
                    u = a.hasAttribute("data-required"),
                    d = this.controlFor(a);
                if (!d) return "";
                if (
                    a.hasAttribute("data-location-field") &&
                    (this.loadingStates || this.loadingCities)
                )
                    return "Please wait for the locations to finish loading.";
                if (a.hasAttribute("data-location-field") && this.locationError)
                    return this.locationError;
                if (f === "file") {
                    if (this.fileProblems[l]) return this.fileProblems[l];
                    const g = d.files?.[0];
                    return g
                        ? this.fileProblem(a, g)
                        : u || d.required
                          ? "Please choose a file."
                          : "";
                }
                if (f === "boolean" || f === "multiple_choice") {
                    const g = a.querySelector("input:checked");
                    return u && !g
                        ? a.dataset.requiredMessage ||
                              "Please choose an answer."
                        : a.hasAttribute("data-must-accept") &&
                            g &&
                            !["1", "yes", "true", "on"].includes(g.value)
                          ? "Please confirm this statement to continue."
                          : "";
                }
                const h = d.value.trim();
                return (u || d.required) && !h
                    ? f === "dropdown"
                        ? "Please choose an option."
                        : "This is required."
                    : h &&
                        f === "email" &&
                        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(h)
                      ? "Enter a valid email address, like name@example.com."
                      : h && f === "numeric" && Number.isNaN(Number(h))
                        ? "Enter a number."
                        : h && d.minLength > 0 && h.length < d.minLength
                          ? `Enter at least ${d.minLength} characters.`
                          : h && !d.checkValidity()
                            ? d.validationMessage || "Please check this value."
                            : "";
            },
            validate(a = null) {
                const l = a === null ? this.fields() : this.stepFields(a);
                return (
                    l.forEach((f) => {
                        if (!this.controlFor(f)) {
                            this.clear(f.dataset.field);
                            return;
                        }
                        const u = this.problemWith(f);
                        u
                            ? (this.errors[f.dataset.field] = u)
                            : this.serverErrors[f.dataset.field] ||
                              delete this.errors[f.dataset.field];
                    }),
                    (this.summary = this.listErrors()),
                    !l.some((f) => this.errors[f.dataset.field]) &&
                        (a !== null || !this.unmappedErrors.length)
                );
            },
            openFirstError() {
                const a = this.summary.find(
                    (l) => Number.isInteger(l.step) && l.step >= 0,
                );
                a && this.moveTo(a.step, !1);
            },
            focusErrors() {
                this.$nextTick(() => this.$refs.summary?.focus());
            },
            async submit(a) {
                if ((a.preventDefault(), !this.submitting)) {
                    if (!this.isLastStep) {
                        this.next();
                        return;
                    }
                    if (
                        ((this.unmappedErrors = []),
                        (this.submissionError = ""),
                        !this.validate())
                    ) {
                        (this.openFirstError(), this.focusErrors());
                        return;
                    }
                    if (this.preview) {
                        ((this.done = !0),
                            this.$nextTick(() => this.$refs.success?.focus()));
                        return;
                    }
                    this.submitting = !0;
                    try {
                        const l = await fetch(this.$refs.form.action, {
                            method: "POST",
                            body: new FormData(this.$refs.form),
                            credentials: "same-origin",
                            headers: {
                                Accept: "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        });
                        if (
                            !l.headers
                                .get("content-type")
                                ?.includes("application/json")
                        )
                            throw new Error(
                                "Please refresh this page and sign in again if your session has expired.",
                            );
                        const f = await l.json();
                        if (l.status === 422) {
                            this.restoreErrors(f.errors || {});
                            return;
                        }
                        if (f.redirect && (l.ok || l.status === 409)) {
                            const u = new URL(f.redirect, window.location.href);
                            if (!["https:", "http:"].includes(u.protocol))
                                throw new Error(
                                    "The application destination is unavailable.",
                                );
                            window.location.assign(u.href);
                            return;
                        }
                        if (!l.ok)
                            throw new Error(
                                l.status === 419
                                    ? "Your session has expired. Refresh this page before trying again."
                                    : "We could not submit your application. Your entries are still here; please try again.",
                            );
                        if (f.submitted !== !0)
                            throw new Error(
                                "We could not confirm your application. Please try again.",
                            );
                        ((this.done = !0),
                            this.$nextTick(() => this.$refs.success?.focus()));
                    } catch (l) {
                        ((this.submissionError =
                            l instanceof TypeError
                                ? "We could not connect. Your entries are still here; please try again."
                                : l.message),
                            this.$nextTick(() =>
                                this.$refs.submissionError?.focus(),
                            ));
                    } finally {
                        this.submitting = !1;
                    }
                }
            },
            goTo(a) {
                (Number.isInteger(a.step) &&
                    a.step >= 0 &&
                    this.moveTo(a.step, !1),
                    this.$nextTick(() => {
                        const l = this.fields().find(
                                (u) => u.dataset.field === a.name,
                            ),
                            f =
                                (l && this.controlFor(l)) ||
                                (a.target
                                    ? document.getElementById(a.target)
                                    : null);
                        (f?.focus(),
                            f?.scrollIntoView({
                                block: "center",
                                behavior: window.matchMedia(
                                    "(prefers-reduced-motion: reduce)",
                                ).matches
                                    ? "auto"
                                    : "smooth",
                            }));
                    }));
            },
            fileProblem(a, l) {
                const f = (a.dataset.accept || "")
                        .split(",")
                        .map((g) => g.trim().replace(/^\./, "").toLowerCase())
                        .filter(Boolean),
                    u = Number(a.dataset.max) || 0,
                    d = l.name.split(".").pop().toLowerCase(),
                    h = f.some((g) =>
                        g.includes("/")
                            ? g.endsWith("/*")
                                ? l.type?.startsWith(g.slice(0, -1))
                                : l.type === g
                            : g === d,
                    );
                return f.length && !h
                    ? `That file type isn't supported. Please choose a ${f.map((g) => g.toUpperCase()).join(", ")} file.`
                    : u && l.size > u
                      ? `That file is ${mo(l.size)}. Please choose one under ${mo(u)}.`
                      : "";
            },
            pick(a) {
                const l = a.target,
                    f = l.closest("[data-field]"),
                    u = f.dataset.field,
                    d = l.files[0];
                if (!d) {
                    this.remove(u, l);
                    return;
                }
                const h = this.fileProblem(f, d);
                (h
                    ? (this.remove(u, l),
                      (this.fileProblems[u] = h),
                      (this.errors[u] = h))
                    : (this.clear(u),
                      delete this.fileProblems[u],
                      (this.files[u] = { name: d.name, size: mo(d.size) })),
                    (this.summary = this.listErrors()));
            },
            drop(a, l) {
                this.over = null;
                const f = this.$refs.form.querySelector(
                    `[data-field="${CSS.escape(l)}"] input[type="file"]`,
                );
                !f ||
                    !a.dataTransfer.files.length ||
                    ((f.files = a.dataTransfer.files),
                    f.dispatchEvent(new Event("change", { bubbles: !0 })));
            },
            remove(a, l = null) {
                (delete this.files[a],
                    delete this.fileProblems[a],
                    this.clear(a));
                const f =
                    l ??
                    this.$refs.form.querySelector(
                        `[data-field="${CSS.escape(a)}"] input[type="file"]`,
                    );
                f && (f.value = "");
            },
        };
    }
    function _m(t) {
        (t.data("applicationForm", (e) => zl(e)),
            t.data("nativeApplicationForm", (e) =>
                zl({ ...e, mode: "native", preview: !1 }),
            ));
    }
    var Mc =
            typeof global == "object" &&
            global &&
            global.Object === Object &&
            global,
        xm = typeof self == "object" && self && self.Object === Object && self,
        vt = Mc || xm || Function("return this")(),
        zt = vt.Symbol,
        Bc = Object.prototype,
        Am = Bc.hasOwnProperty,
        Sm = Bc.toString,
        On = zt ? zt.toStringTag : void 0;
    function Tm(t) {
        var e = Am.call(t, On),
            r = t[On];
        try {
            t[On] = void 0;
            var n = !0;
        } catch {}
        var s = Sm.call(t);
        return (n && (e ? (t[On] = r) : delete t[On]), s);
    }
    var Nm = Object.prototype,
        Om = Nm.toString;
    function Cm(t) {
        return Om.call(t);
    }
    var Lm = "[object Null]",
        Rm = "[object Undefined]",
        Kl = zt ? zt.toStringTag : void 0;
    function an(t) {
        return t == null
            ? t === void 0
                ? Rm
                : Lm
            : Kl && Kl in Object(t)
              ? Tm(t)
              : Cm(t);
    }
    function Ct(t) {
        return t != null && typeof t == "object";
    }
    var mr = Array.isArray;
    function Wt(t) {
        var e = typeof t;
        return t != null && (e == "object" || e == "function");
    }
    function $c(t) {
        return t;
    }
    var qm = "[object AsyncFunction]",
        km = "[object Function]",
        Im = "[object GeneratorFunction]",
        Pm = "[object Proxy]";
    function Ka(t) {
        if (!Wt(t)) return !1;
        var e = an(t);
        return e == km || e == Im || e == qm || e == Pm;
    }
    var bo = vt["__core-js_shared__"],
        Wl = (function () {
            var t = /[^.]+$/.exec((bo && bo.keys && bo.keys.IE_PROTO) || "");
            return t ? "Symbol(src)_1." + t : "";
        })();
    function Mm(t) {
        return !!Wl && Wl in t;
    }
    var Bm = Function.prototype,
        $m = Bm.toString;
    function Er(t) {
        if (t != null) {
            try {
                return $m.call(t);
            } catch {}
            try {
                return t + "";
            } catch {}
        }
        return "";
    }
    var Dm = /[\\^$.*+?()[\]{}|]/g,
        jm = /^\[object .+?Constructor\]$/,
        Um = Function.prototype,
        Fm = Object.prototype,
        Hm = Um.toString,
        zm = Fm.hasOwnProperty,
        Km = RegExp(
            "^" +
                Hm.call(zm)
                    .replace(Dm, "\\$&")
                    .replace(
                        /hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,
                        "$1.*?",
                    ) +
                "$",
        );
    function Wm(t) {
        if (!Wt(t) || Mm(t)) return !1;
        var e = Ka(t) ? Km : jm;
        return e.test(Er(t));
    }
    function Vm(t, e) {
        return t?.[e];
    }
    function _r(t, e) {
        var r = Vm(t, e);
        return Wm(r) ? r : void 0;
    }
    var Jo = _r(vt, "WeakMap"),
        Vl = Object.create,
        Gm = (function () {
            function t() {}
            return function (e) {
                if (!Wt(e)) return {};
                if (Vl) return Vl(e);
                t.prototype = e;
                var r = new t();
                return ((t.prototype = void 0), r);
            };
        })();
    function Zm(t, e, r) {
        switch (r.length) {
            case 0:
                return t.call(e);
            case 1:
                return t.call(e, r[0]);
            case 2:
                return t.call(e, r[0], r[1]);
            case 3:
                return t.call(e, r[0], r[1], r[2]);
        }
        return t.apply(e, r);
    }
    function Xm(t, e) {
        var r = -1,
            n = t.length;
        for (e || (e = Array(n)); ++r < n; ) e[r] = t[r];
        return e;
    }
    var Jm = 800,
        Ym = 16,
        Qm = Date.now;
    function e1(t) {
        var e = 0,
            r = 0;
        return function () {
            var n = Qm(),
                s = Ym - (n - r);
            if (((r = n), s > 0)) {
                if (++e >= Jm) return arguments[0];
            } else e = 0;
            return t.apply(void 0, arguments);
        };
    }
    function t1(t) {
        return function () {
            return t;
        };
    }
    var Js = (function () {
            try {
                var t = _r(Object, "defineProperty");
                return (t({}, "", {}), t);
            } catch {}
        })(),
        r1 = Js
            ? function (t, e) {
                  return Js(t, "toString", {
                      configurable: !0,
                      enumerable: !1,
                      value: t1(e),
                      writable: !0,
                  });
              }
            : $c,
        n1 = e1(r1);
    function s1(t, e) {
        for (
            var r = -1, n = t == null ? 0 : t.length;
            ++r < n && e(t[r], r, t) !== !1;
        );
        return t;
    }
    var i1 = 9007199254740991,
        o1 = /^(?:0|[1-9]\d*)$/;
    function Dc(t, e) {
        var r = typeof t;
        return (
            (e = e ?? i1),
            !!e &&
                (r == "number" || (r != "symbol" && o1.test(t))) &&
                t > -1 &&
                t % 1 == 0 &&
                t < e
        );
    }
    function Wa(t, e, r) {
        e == "__proto__" && Js
            ? Js(t, e, {
                  configurable: !0,
                  enumerable: !0,
                  value: r,
                  writable: !0,
              })
            : (t[e] = r);
    }
    function es(t, e) {
        return t === e || (t !== t && e !== e);
    }
    var a1 = Object.prototype,
        l1 = a1.hasOwnProperty;
    function jc(t, e, r) {
        var n = t[e];
        (!(l1.call(t, e) && es(n, r)) || (r === void 0 && !(e in t))) &&
            Wa(t, e, r);
    }
    function f1(t, e, r, n) {
        var s = !r;
        r || (r = {});
        for (var i = -1, o = e.length; ++i < o; ) {
            var a = e[i],
                l = void 0;
            (l === void 0 && (l = t[a]), s ? Wa(r, a, l) : jc(r, a, l));
        }
        return r;
    }
    var Gl = Math.max;
    function c1(t, e, r) {
        return (
            (e = Gl(e === void 0 ? t.length - 1 : e, 0)),
            function () {
                for (
                    var n = arguments,
                        s = -1,
                        i = Gl(n.length - e, 0),
                        o = Array(i);
                    ++s < i;
                )
                    o[s] = n[e + s];
                s = -1;
                for (var a = Array(e + 1); ++s < e; ) a[s] = n[s];
                return ((a[e] = r(o)), Zm(t, this, a));
            }
        );
    }
    function u1(t, e) {
        return n1(c1(t, e, $c), t + "");
    }
    var h1 = 9007199254740991;
    function Uc(t) {
        return typeof t == "number" && t > -1 && t % 1 == 0 && t <= h1;
    }
    function di(t) {
        return t != null && Uc(t.length) && !Ka(t);
    }
    function d1(t, e, r) {
        if (!Wt(r)) return !1;
        var n = typeof e;
        return (
            n == "number" ? di(r) && Dc(e, r.length) : n == "string" && e in r
        )
            ? es(r[e], t)
            : !1;
    }
    function p1(t) {
        return u1(function (e, r) {
            var n = -1,
                s = r.length,
                i = s > 1 ? r[s - 1] : void 0,
                o = s > 2 ? r[2] : void 0;
            for (
                i = t.length > 3 && typeof i == "function" ? (s--, i) : void 0,
                    o &&
                        d1(r[0], r[1], o) &&
                        ((i = s < 3 ? void 0 : i), (s = 1)),
                    e = Object(e);
                ++n < s;
            ) {
                var a = r[n];
                a && t(e, a, n, i);
            }
            return e;
        });
    }
    var g1 = Object.prototype;
    function Va(t) {
        var e = t && t.constructor,
            r = (typeof e == "function" && e.prototype) || g1;
        return t === r;
    }
    function m1(t, e) {
        for (var r = -1, n = Array(t); ++r < t; ) n[r] = e(r);
        return n;
    }
    var b1 = "[object Arguments]";
    function Zl(t) {
        return Ct(t) && an(t) == b1;
    }
    var Fc = Object.prototype,
        y1 = Fc.hasOwnProperty,
        v1 = Fc.propertyIsEnumerable,
        Yo = Zl(
            (function () {
                return arguments;
            })(),
        )
            ? Zl
            : function (t) {
                  return Ct(t) && y1.call(t, "callee") && !v1.call(t, "callee");
              };
    function w1() {
        return !1;
    }
    var Hc = typeof Ue == "object" && Ue && !Ue.nodeType && Ue,
        Xl = Hc && typeof Fe == "object" && Fe && !Fe.nodeType && Fe,
        E1 = Xl && Xl.exports === Hc,
        Jl = E1 ? vt.Buffer : void 0,
        _1 = Jl ? Jl.isBuffer : void 0,
        Hn = _1 || w1,
        x1 = "[object Arguments]",
        A1 = "[object Array]",
        S1 = "[object Boolean]",
        T1 = "[object Date]",
        N1 = "[object Error]",
        O1 = "[object Function]",
        C1 = "[object Map]",
        L1 = "[object Number]",
        R1 = "[object Object]",
        q1 = "[object RegExp]",
        k1 = "[object Set]",
        I1 = "[object String]",
        P1 = "[object WeakMap]",
        M1 = "[object ArrayBuffer]",
        B1 = "[object DataView]",
        $1 = "[object Float32Array]",
        D1 = "[object Float64Array]",
        j1 = "[object Int8Array]",
        U1 = "[object Int16Array]",
        F1 = "[object Int32Array]",
        H1 = "[object Uint8Array]",
        z1 = "[object Uint8ClampedArray]",
        K1 = "[object Uint16Array]",
        W1 = "[object Uint32Array]",
        pe = {};
    pe[$1] =
        pe[D1] =
        pe[j1] =
        pe[U1] =
        pe[F1] =
        pe[H1] =
        pe[z1] =
        pe[K1] =
        pe[W1] =
            !0;
    pe[x1] =
        pe[A1] =
        pe[M1] =
        pe[S1] =
        pe[B1] =
        pe[T1] =
        pe[N1] =
        pe[O1] =
        pe[C1] =
        pe[L1] =
        pe[R1] =
        pe[q1] =
        pe[k1] =
        pe[I1] =
        pe[P1] =
            !1;
    function V1(t) {
        return Ct(t) && Uc(t.length) && !!pe[an(t)];
    }
    function Ga(t) {
        return function (e) {
            return t(e);
        };
    }
    var zc = typeof Ue == "object" && Ue && !Ue.nodeType && Ue,
        Bn = zc && typeof Fe == "object" && Fe && !Fe.nodeType && Fe,
        G1 = Bn && Bn.exports === zc,
        yo = G1 && Mc.process,
        Jr = (function () {
            try {
                var t = Bn && Bn.require && Bn.require("util").types;
                return t || (yo && yo.binding && yo.binding("util"));
            } catch {}
        })(),
        Yl = Jr && Jr.isTypedArray,
        Za = Yl ? Ga(Yl) : V1,
        Z1 = Object.prototype,
        X1 = Z1.hasOwnProperty;
    function Kc(t, e) {
        var r = mr(t),
            n = !r && Yo(t),
            s = !r && !n && Hn(t),
            i = !r && !n && !s && Za(t),
            o = r || n || s || i,
            a = o ? m1(t.length, String) : [],
            l = a.length;
        for (var f in t)
            (e || X1.call(t, f)) &&
                !(
                    o &&
                    (f == "length" ||
                        (s && (f == "offset" || f == "parent")) ||
                        (i &&
                            (f == "buffer" ||
                                f == "byteLength" ||
                                f == "byteOffset")) ||
                        Dc(f, l))
                ) &&
                a.push(f);
        return a;
    }
    function Wc(t, e) {
        return function (r) {
            return t(e(r));
        };
    }
    var J1 = Wc(Object.keys, Object),
        Y1 = Object.prototype,
        Q1 = Y1.hasOwnProperty;
    function eb(t) {
        if (!Va(t)) return J1(t);
        var e = [];
        for (var r in Object(t))
            Q1.call(t, r) && r != "constructor" && e.push(r);
        return e;
    }
    function tb(t) {
        return di(t) ? Kc(t) : eb(t);
    }
    function rb(t) {
        var e = [];
        if (t != null) for (var r in Object(t)) e.push(r);
        return e;
    }
    var nb = Object.prototype,
        sb = nb.hasOwnProperty;
    function ib(t) {
        if (!Wt(t)) return rb(t);
        var e = Va(t),
            r = [];
        for (var n in t)
            (n == "constructor" && (e || !sb.call(t, n))) || r.push(n);
        return r;
    }
    function Vc(t) {
        return di(t) ? Kc(t, !0) : ib(t);
    }
    var zn = _r(Object, "create");
    function ob() {
        ((this.__data__ = zn ? zn(null) : {}), (this.size = 0));
    }
    function ab(t) {
        var e = this.has(t) && delete this.__data__[t];
        return ((this.size -= e ? 1 : 0), e);
    }
    var lb = "__lodash_hash_undefined__",
        fb = Object.prototype,
        cb = fb.hasOwnProperty;
    function ub(t) {
        var e = this.__data__;
        if (zn) {
            var r = e[t];
            return r === lb ? void 0 : r;
        }
        return cb.call(e, t) ? e[t] : void 0;
    }
    var hb = Object.prototype,
        db = hb.hasOwnProperty;
    function pb(t) {
        var e = this.__data__;
        return zn ? e[t] !== void 0 : db.call(e, t);
    }
    var gb = "__lodash_hash_undefined__";
    function mb(t, e) {
        var r = this.__data__;
        return (
            (this.size += this.has(t) ? 0 : 1),
            (r[t] = zn && e === void 0 ? gb : e),
            this
        );
    }
    function br(t) {
        var e = -1,
            r = t == null ? 0 : t.length;
        for (this.clear(); ++e < r; ) {
            var n = t[e];
            this.set(n[0], n[1]);
        }
    }
    br.prototype.clear = ob;
    br.prototype.delete = ab;
    br.prototype.get = ub;
    br.prototype.has = pb;
    br.prototype.set = mb;
    function bb() {
        ((this.__data__ = []), (this.size = 0));
    }
    function pi(t, e) {
        for (var r = t.length; r--; ) if (es(t[r][0], e)) return r;
        return -1;
    }
    var yb = Array.prototype,
        vb = yb.splice;
    function wb(t) {
        var e = this.__data__,
            r = pi(e, t);
        if (r < 0) return !1;
        var n = e.length - 1;
        return (r == n ? e.pop() : vb.call(e, r, 1), --this.size, !0);
    }
    function Eb(t) {
        var e = this.__data__,
            r = pi(e, t);
        return r < 0 ? void 0 : e[r][1];
    }
    function _b(t) {
        return pi(this.__data__, t) > -1;
    }
    function xb(t, e) {
        var r = this.__data__,
            n = pi(r, t);
        return (n < 0 ? (++this.size, r.push([t, e])) : (r[n][1] = e), this);
    }
    function Rt(t) {
        var e = -1,
            r = t == null ? 0 : t.length;
        for (this.clear(); ++e < r; ) {
            var n = t[e];
            this.set(n[0], n[1]);
        }
    }
    Rt.prototype.clear = bb;
    Rt.prototype.delete = wb;
    Rt.prototype.get = Eb;
    Rt.prototype.has = _b;
    Rt.prototype.set = xb;
    var Kn = _r(vt, "Map");
    function Ab() {
        ((this.size = 0),
            (this.__data__ = {
                hash: new br(),
                map: new (Kn || Rt)(),
                string: new br(),
            }));
    }
    function Sb(t) {
        var e = typeof t;
        return e == "string" || e == "number" || e == "symbol" || e == "boolean"
            ? t !== "__proto__"
            : t === null;
    }
    function gi(t, e) {
        var r = t.__data__;
        return Sb(e) ? r[typeof e == "string" ? "string" : "hash"] : r.map;
    }
    function Tb(t) {
        var e = gi(this, t).delete(t);
        return ((this.size -= e ? 1 : 0), e);
    }
    function Nb(t) {
        return gi(this, t).get(t);
    }
    function Ob(t) {
        return gi(this, t).has(t);
    }
    function Cb(t, e) {
        var r = gi(this, t),
            n = r.size;
        return (r.set(t, e), (this.size += r.size == n ? 0 : 1), this);
    }
    function xr(t) {
        var e = -1,
            r = t == null ? 0 : t.length;
        for (this.clear(); ++e < r; ) {
            var n = t[e];
            this.set(n[0], n[1]);
        }
    }
    xr.prototype.clear = Ab;
    xr.prototype.delete = Tb;
    xr.prototype.get = Nb;
    xr.prototype.has = Ob;
    xr.prototype.set = Cb;
    function Lb(t, e) {
        for (var r = -1, n = e.length, s = t.length; ++r < n; ) t[s + r] = e[r];
        return t;
    }
    var Gc = Wc(Object.getPrototypeOf, Object),
        Rb = "[object Object]",
        qb = Function.prototype,
        kb = Object.prototype,
        Zc = qb.toString,
        Ib = kb.hasOwnProperty,
        Pb = Zc.call(Object);
    function Mb(t) {
        if (!Ct(t) || an(t) != Rb) return !1;
        var e = Gc(t);
        if (e === null) return !0;
        var r = Ib.call(e, "constructor") && e.constructor;
        return typeof r == "function" && r instanceof r && Zc.call(r) == Pb;
    }
    function Bb() {
        ((this.__data__ = new Rt()), (this.size = 0));
    }
    function $b(t) {
        var e = this.__data__,
            r = e.delete(t);
        return ((this.size = e.size), r);
    }
    function Db(t) {
        return this.__data__.get(t);
    }
    function jb(t) {
        return this.__data__.has(t);
    }
    var Ub = 200;
    function Fb(t, e) {
        var r = this.__data__;
        if (r instanceof Rt) {
            var n = r.__data__;
            if (!Kn || n.length < Ub - 1)
                return (n.push([t, e]), (this.size = ++r.size), this);
            r = this.__data__ = new xr(n);
        }
        return (r.set(t, e), (this.size = r.size), this);
    }
    function mt(t) {
        var e = (this.__data__ = new Rt(t));
        this.size = e.size;
    }
    mt.prototype.clear = Bb;
    mt.prototype.delete = $b;
    mt.prototype.get = Db;
    mt.prototype.has = jb;
    mt.prototype.set = Fb;
    var Xc = typeof Ue == "object" && Ue && !Ue.nodeType && Ue,
        Ql = Xc && typeof Fe == "object" && Fe && !Fe.nodeType && Fe,
        Hb = Ql && Ql.exports === Xc,
        e0 = Hb ? vt.Buffer : void 0,
        t0 = e0 ? e0.allocUnsafe : void 0;
    function Jc(t, e) {
        if (e) return t.slice();
        var r = t.length,
            n = t0 ? t0(r) : new t.constructor(r);
        return (t.copy(n), n);
    }
    function zb(t, e) {
        for (
            var r = -1, n = t == null ? 0 : t.length, s = 0, i = [];
            ++r < n;
        ) {
            var o = t[r];
            e(o, r, t) && (i[s++] = o);
        }
        return i;
    }
    function Kb() {
        return [];
    }
    var Wb = Object.prototype,
        Vb = Wb.propertyIsEnumerable,
        r0 = Object.getOwnPropertySymbols,
        Gb = r0
            ? function (t) {
                  return t == null
                      ? []
                      : ((t = Object(t)),
                        zb(r0(t), function (e) {
                            return Vb.call(t, e);
                        }));
              }
            : Kb;
    function Zb(t, e, r) {
        var n = e(t);
        return mr(t) ? n : Lb(n, r(t));
    }
    function Qo(t) {
        return Zb(t, tb, Gb);
    }
    var ea = _r(vt, "DataView"),
        ta = _r(vt, "Promise"),
        ra = _r(vt, "Set"),
        n0 = "[object Map]",
        Xb = "[object Object]",
        s0 = "[object Promise]",
        i0 = "[object Set]",
        o0 = "[object WeakMap]",
        a0 = "[object DataView]",
        Jb = Er(ea),
        Yb = Er(Kn),
        Qb = Er(ta),
        ey = Er(ra),
        ty = Er(Jo),
        et = an;
    ((ea && et(new ea(new ArrayBuffer(1))) != a0) ||
        (Kn && et(new Kn()) != n0) ||
        (ta && et(ta.resolve()) != s0) ||
        (ra && et(new ra()) != i0) ||
        (Jo && et(new Jo()) != o0)) &&
        (et = function (t) {
            var e = an(t),
                r = e == Xb ? t.constructor : void 0,
                n = r ? Er(r) : "";
            if (n)
                switch (n) {
                    case Jb:
                        return a0;
                    case Yb:
                        return n0;
                    case Qb:
                        return s0;
                    case ey:
                        return i0;
                    case ty:
                        return o0;
                }
            return e;
        });
    var ry = Object.prototype,
        ny = ry.hasOwnProperty;
    function sy(t) {
        var e = t.length,
            r = new t.constructor(e);
        return (
            e &&
                typeof t[0] == "string" &&
                ny.call(t, "index") &&
                ((r.index = t.index), (r.input = t.input)),
            r
        );
    }
    var Ys = vt.Uint8Array;
    function Xa(t) {
        var e = new t.constructor(t.byteLength);
        return (new Ys(e).set(new Ys(t)), e);
    }
    function iy(t, e) {
        var r = Xa(t.buffer);
        return new t.constructor(r, t.byteOffset, t.byteLength);
    }
    var oy = /\w*$/;
    function ay(t) {
        var e = new t.constructor(t.source, oy.exec(t));
        return ((e.lastIndex = t.lastIndex), e);
    }
    var l0 = zt ? zt.prototype : void 0,
        f0 = l0 ? l0.valueOf : void 0;
    function ly(t) {
        return f0 ? Object(f0.call(t)) : {};
    }
    function Yc(t, e) {
        var r = e ? Xa(t.buffer) : t.buffer;
        return new t.constructor(r, t.byteOffset, t.length);
    }
    var fy = "[object Boolean]",
        cy = "[object Date]",
        uy = "[object Map]",
        hy = "[object Number]",
        dy = "[object RegExp]",
        py = "[object Set]",
        gy = "[object String]",
        my = "[object Symbol]",
        by = "[object ArrayBuffer]",
        yy = "[object DataView]",
        vy = "[object Float32Array]",
        wy = "[object Float64Array]",
        Ey = "[object Int8Array]",
        _y = "[object Int16Array]",
        xy = "[object Int32Array]",
        Ay = "[object Uint8Array]",
        Sy = "[object Uint8ClampedArray]",
        Ty = "[object Uint16Array]",
        Ny = "[object Uint32Array]";
    function Oy(t, e, r) {
        var n = t.constructor;
        switch (e) {
            case by:
                return Xa(t);
            case fy:
            case cy:
                return new n(+t);
            case yy:
                return iy(t);
            case vy:
            case wy:
            case Ey:
            case _y:
            case xy:
            case Ay:
            case Sy:
            case Ty:
            case Ny:
                return Yc(t, r);
            case uy:
                return new n();
            case hy:
            case gy:
                return new n(t);
            case dy:
                return ay(t);
            case py:
                return new n();
            case my:
                return ly(t);
        }
    }
    function Qc(t) {
        return typeof t.constructor == "function" && !Va(t) ? Gm(Gc(t)) : {};
    }
    var Cy = "[object Map]";
    function Ly(t) {
        return Ct(t) && et(t) == Cy;
    }
    var c0 = Jr && Jr.isMap,
        Ry = c0 ? Ga(c0) : Ly,
        qy = "[object Set]";
    function ky(t) {
        return Ct(t) && et(t) == qy;
    }
    var u0 = Jr && Jr.isSet,
        Iy = u0 ? Ga(u0) : ky,
        Py = 1,
        eu = "[object Arguments]",
        My = "[object Array]",
        By = "[object Boolean]",
        $y = "[object Date]",
        Dy = "[object Error]",
        tu = "[object Function]",
        jy = "[object GeneratorFunction]",
        Uy = "[object Map]",
        Fy = "[object Number]",
        ru = "[object Object]",
        Hy = "[object RegExp]",
        zy = "[object Set]",
        Ky = "[object String]",
        Wy = "[object Symbol]",
        Vy = "[object WeakMap]",
        Gy = "[object ArrayBuffer]",
        Zy = "[object DataView]",
        Xy = "[object Float32Array]",
        Jy = "[object Float64Array]",
        Yy = "[object Int8Array]",
        Qy = "[object Int16Array]",
        ev = "[object Int32Array]",
        tv = "[object Uint8Array]",
        rv = "[object Uint8ClampedArray]",
        nv = "[object Uint16Array]",
        sv = "[object Uint32Array]",
        he = {};
    he[eu] =
        he[My] =
        he[Gy] =
        he[Zy] =
        he[By] =
        he[$y] =
        he[Xy] =
        he[Jy] =
        he[Yy] =
        he[Qy] =
        he[ev] =
        he[Uy] =
        he[Fy] =
        he[ru] =
        he[Hy] =
        he[zy] =
        he[Ky] =
        he[Wy] =
        he[tv] =
        he[rv] =
        he[nv] =
        he[sv] =
            !0;
    he[Dy] = he[tu] = he[Vy] = !1;
    function Hs(t, e, r, n, s, i) {
        var o,
            a = e & Py;
        if (o !== void 0) return o;
        if (!Wt(t)) return t;
        var l = mr(t);
        if (l) o = sy(t);
        else {
            var f = et(t),
                u = f == tu || f == jy;
            if (Hn(t)) return Jc(t, a);
            if (f == ru || f == eu || (u && !s)) o = u ? {} : Qc(t);
            else {
                if (!he[f]) return s ? t : {};
                o = Oy(t, f, a);
            }
        }
        i || (i = new mt());
        var d = i.get(t);
        if (d) return d;
        (i.set(t, o),
            Iy(t)
                ? t.forEach(function (v) {
                      o.add(Hs(v, e, r, v, t, i));
                  })
                : Ry(t) &&
                  t.forEach(function (v, b) {
                      o.set(b, Hs(v, e, r, b, t, i));
                  }));
        var h = Qo,
            g = l ? void 0 : h(t);
        return (
            s1(g || t, function (v, b) {
                (g && ((b = v), (v = t[b])), jc(o, b, Hs(v, e, r, b, t, i)));
            }),
            o
        );
    }
    var iv = 1,
        ov = 4;
    function Kr(t) {
        return Hs(t, iv | ov);
    }
    var av = "__lodash_hash_undefined__";
    function lv(t) {
        return (this.__data__.set(t, av), this);
    }
    function fv(t) {
        return this.__data__.has(t);
    }
    function Qs(t) {
        var e = -1,
            r = t == null ? 0 : t.length;
        for (this.__data__ = new xr(); ++e < r; ) this.add(t[e]);
    }
    Qs.prototype.add = Qs.prototype.push = lv;
    Qs.prototype.has = fv;
    function cv(t, e) {
        for (var r = -1, n = t == null ? 0 : t.length; ++r < n; )
            if (e(t[r], r, t)) return !0;
        return !1;
    }
    function uv(t, e) {
        return t.has(e);
    }
    var hv = 1,
        dv = 2;
    function nu(t, e, r, n, s, i) {
        var o = r & hv,
            a = t.length,
            l = e.length;
        if (a != l && !(o && l > a)) return !1;
        var f = i.get(t),
            u = i.get(e);
        if (f && u) return f == e && u == t;
        var d = -1,
            h = !0,
            g = r & dv ? new Qs() : void 0;
        for (i.set(t, e), i.set(e, t); ++d < a; ) {
            var v = t[d],
                b = e[d];
            if (n) var _ = o ? n(b, v, d, e, t, i) : n(v, b, d, t, e, i);
            if (_ !== void 0) {
                if (_) continue;
                h = !1;
                break;
            }
            if (g) {
                if (
                    !cv(e, function (y, S) {
                        if (!uv(g, S) && (v === y || s(v, y, r, n, i)))
                            return g.push(S);
                    })
                ) {
                    h = !1;
                    break;
                }
            } else if (!(v === b || s(v, b, r, n, i))) {
                h = !1;
                break;
            }
        }
        return (i.delete(t), i.delete(e), h);
    }
    function pv(t) {
        var e = -1,
            r = Array(t.size);
        return (
            t.forEach(function (n, s) {
                r[++e] = [s, n];
            }),
            r
        );
    }
    function gv(t) {
        var e = -1,
            r = Array(t.size);
        return (
            t.forEach(function (n) {
                r[++e] = n;
            }),
            r
        );
    }
    var mv = 1,
        bv = 2,
        yv = "[object Boolean]",
        vv = "[object Date]",
        wv = "[object Error]",
        Ev = "[object Map]",
        _v = "[object Number]",
        xv = "[object RegExp]",
        Av = "[object Set]",
        Sv = "[object String]",
        Tv = "[object Symbol]",
        Nv = "[object ArrayBuffer]",
        Ov = "[object DataView]",
        h0 = zt ? zt.prototype : void 0,
        vo = h0 ? h0.valueOf : void 0;
    function Cv(t, e, r, n, s, i, o) {
        switch (r) {
            case Ov:
                if (
                    t.byteLength != e.byteLength ||
                    t.byteOffset != e.byteOffset
                )
                    return !1;
                ((t = t.buffer), (e = e.buffer));
            case Nv:
                return !(
                    t.byteLength != e.byteLength || !i(new Ys(t), new Ys(e))
                );
            case yv:
            case vv:
            case _v:
                return es(+t, +e);
            case wv:
                return t.name == e.name && t.message == e.message;
            case xv:
            case Sv:
                return t == e + "";
            case Ev:
                var a = pv;
            case Av:
                var l = n & mv;
                if ((a || (a = gv), t.size != e.size && !l)) return !1;
                var f = o.get(t);
                if (f) return f == e;
                ((n |= bv), o.set(t, e));
                var u = nu(a(t), a(e), n, s, i, o);
                return (o.delete(t), u);
            case Tv:
                if (vo) return vo.call(t) == vo.call(e);
        }
        return !1;
    }
    var Lv = 1,
        Rv = Object.prototype,
        qv = Rv.hasOwnProperty;
    function kv(t, e, r, n, s, i) {
        var o = r & Lv,
            a = Qo(t),
            l = a.length,
            f = Qo(e),
            u = f.length;
        if (l != u && !o) return !1;
        for (var d = l; d--; ) {
            var h = a[d];
            if (!(o ? h in e : qv.call(e, h))) return !1;
        }
        var g = i.get(t),
            v = i.get(e);
        if (g && v) return g == e && v == t;
        var b = !0;
        (i.set(t, e), i.set(e, t));
        for (var _ = o; ++d < l; ) {
            h = a[d];
            var y = t[h],
                S = e[h];
            if (n) var N = o ? n(S, y, h, e, t, i) : n(y, S, h, t, e, i);
            if (!(N === void 0 ? y === S || s(y, S, r, n, i) : N)) {
                b = !1;
                break;
            }
            _ || (_ = h == "constructor");
        }
        if (b && !_) {
            var P = t.constructor,
                q = e.constructor;
            P != q &&
                "constructor" in t &&
                "constructor" in e &&
                !(
                    typeof P == "function" &&
                    P instanceof P &&
                    typeof q == "function" &&
                    q instanceof q
                ) &&
                (b = !1);
        }
        return (i.delete(t), i.delete(e), b);
    }
    var Iv = 1,
        d0 = "[object Arguments]",
        p0 = "[object Array]",
        Cs = "[object Object]",
        Pv = Object.prototype,
        g0 = Pv.hasOwnProperty;
    function Mv(t, e, r, n, s, i) {
        var o = mr(t),
            a = mr(e),
            l = o ? p0 : et(t),
            f = a ? p0 : et(e);
        ((l = l == d0 ? Cs : l), (f = f == d0 ? Cs : f));
        var u = l == Cs,
            d = f == Cs,
            h = l == f;
        if (h && Hn(t)) {
            if (!Hn(e)) return !1;
            ((o = !0), (u = !1));
        }
        if (h && !u)
            return (
                i || (i = new mt()),
                o || Za(t) ? nu(t, e, r, n, s, i) : Cv(t, e, l, r, n, s, i)
            );
        if (!(r & Iv)) {
            var g = u && g0.call(t, "__wrapped__"),
                v = d && g0.call(e, "__wrapped__");
            if (g || v) {
                var b = g ? t.value() : t,
                    _ = v ? e.value() : e;
                return (i || (i = new mt()), s(b, _, r, n, i));
            }
        }
        return h ? (i || (i = new mt()), kv(t, e, r, n, s, i)) : !1;
    }
    function su(t, e, r, n, s) {
        return t === e
            ? !0
            : t == null || e == null || (!Ct(t) && !Ct(e))
              ? t !== t && e !== e
              : Mv(t, e, r, n, su, s);
    }
    function Bv(t) {
        return function (e, r, n) {
            for (var s = -1, i = Object(e), o = n(e), a = o.length; a--; ) {
                var l = o[++s];
                if (r(i[l], l, i) === !1) break;
            }
            return e;
        };
    }
    var $v = Bv();
    function na(t, e, r) {
        ((r !== void 0 && !es(t[e], r)) || (r === void 0 && !(e in t))) &&
            Wa(t, e, r);
    }
    function Dv(t) {
        return Ct(t) && di(t);
    }
    function sa(t, e) {
        if (
            !(e === "constructor" && typeof t[e] == "function") &&
            e != "__proto__"
        )
            return t[e];
    }
    function jv(t) {
        return f1(t, Vc(t));
    }
    function Uv(t, e, r, n, s, i, o) {
        var a = sa(t, r),
            l = sa(e, r),
            f = o.get(l);
        if (f) {
            na(t, r, f);
            return;
        }
        var u = i ? i(a, l, r + "", t, e, o) : void 0,
            d = u === void 0;
        if (d) {
            var h = mr(l),
                g = !h && Hn(l),
                v = !h && !g && Za(l);
            ((u = l),
                h || g || v
                    ? mr(a)
                        ? (u = a)
                        : Dv(a)
                          ? (u = Xm(a))
                          : g
                            ? ((d = !1), (u = Jc(l, !0)))
                            : v
                              ? ((d = !1), (u = Yc(l, !0)))
                              : (u = [])
                    : Mb(l) || Yo(l)
                      ? ((u = a),
                        Yo(a) ? (u = jv(a)) : (!Wt(a) || Ka(a)) && (u = Qc(l)))
                      : (d = !1));
        }
        (d && (o.set(l, u), s(u, l, n, i, o), o.delete(l)), na(t, r, u));
    }
    function iu(t, e, r, n, s) {
        t !== e &&
            $v(
                e,
                function (i, o) {
                    if ((s || (s = new mt()), Wt(i))) Uv(t, e, o, r, iu, n, s);
                    else {
                        var a = n ? n(sa(t, o), i, o + "", t, e, s) : void 0;
                        (a === void 0 && (a = i), na(t, o, a));
                    }
                },
                Vc,
            );
    }
    function Ja(t, e) {
        return su(t, e);
    }
    var jt = p1(function (t, e, r) {
            iu(t, e, r);
        }),
        K = ((t) => (
            (t[(t.TYPE = 3)] = "TYPE"),
            (t[(t.LEVEL = 12)] = "LEVEL"),
            (t[(t.ATTRIBUTE = 13)] = "ATTRIBUTE"),
            (t[(t.BLOT = 14)] = "BLOT"),
            (t[(t.INLINE = 7)] = "INLINE"),
            (t[(t.BLOCK = 11)] = "BLOCK"),
            (t[(t.BLOCK_BLOT = 10)] = "BLOCK_BLOT"),
            (t[(t.INLINE_BLOT = 6)] = "INLINE_BLOT"),
            (t[(t.BLOCK_ATTRIBUTE = 9)] = "BLOCK_ATTRIBUTE"),
            (t[(t.INLINE_ATTRIBUTE = 5)] = "INLINE_ATTRIBUTE"),
            (t[(t.ANY = 15)] = "ANY"),
            t
        ))(K || {});
    class yt {
        constructor(e, r, n = {}) {
            ((this.attrName = e), (this.keyName = r));
            const s = K.TYPE & K.ATTRIBUTE;
            ((this.scope =
                n.scope != null ? (n.scope & K.LEVEL) | s : K.ATTRIBUTE),
                n.whitelist != null && (this.whitelist = n.whitelist));
        }
        static keys(e) {
            return Array.from(e.attributes).map((r) => r.name);
        }
        add(e, r) {
            return this.canAdd(e, r)
                ? (e.setAttribute(this.keyName, r), !0)
                : !1;
        }
        canAdd(e, r) {
            return this.whitelist == null
                ? !0
                : typeof r == "string"
                  ? this.whitelist.indexOf(r.replace(/["']/g, "")) > -1
                  : this.whitelist.indexOf(r) > -1;
        }
        remove(e) {
            e.removeAttribute(this.keyName);
        }
        value(e) {
            const r = e.getAttribute(this.keyName);
            return this.canAdd(e, r) && r ? r : "";
        }
    }
    class Wr extends Error {
        constructor(e) {
            ((e = "[Parchment] " + e),
                super(e),
                (this.message = e),
                (this.name = this.constructor.name));
        }
    }
    const ou = class ia {
        constructor() {
            ((this.attributes = {}),
                (this.classes = {}),
                (this.tags = {}),
                (this.types = {}));
        }
        static find(e, r = !1) {
            if (e == null) return null;
            if (this.blots.has(e)) return this.blots.get(e) || null;
            if (r) {
                let n = null;
                try {
                    n = e.parentNode;
                } catch {
                    return null;
                }
                return this.find(n, r);
            }
            return null;
        }
        create(e, r, n) {
            const s = this.query(r);
            if (s == null) throw new Wr(`Unable to create ${r} blot`);
            const i = s,
                o =
                    r instanceof Node || r.nodeType === Node.TEXT_NODE
                        ? r
                        : i.create(n),
                a = new i(e, o, n);
            return (ia.blots.set(a.domNode, a), a);
        }
        find(e, r = !1) {
            return ia.find(e, r);
        }
        query(e, r = K.ANY) {
            let n;
            return (
                typeof e == "string"
                    ? (n = this.types[e] || this.attributes[e])
                    : e instanceof Text || e.nodeType === Node.TEXT_NODE
                      ? (n = this.types.text)
                      : typeof e == "number"
                        ? e & K.LEVEL & K.BLOCK
                            ? (n = this.types.block)
                            : e & K.LEVEL & K.INLINE && (n = this.types.inline)
                        : e instanceof Element &&
                          ((e.getAttribute("class") || "")
                              .split(/\s+/)
                              .some((s) => ((n = this.classes[s]), !!n)),
                          (n = n || this.tags[e.tagName])),
                n == null
                    ? null
                    : "scope" in n &&
                        r & K.LEVEL & n.scope &&
                        r & K.TYPE & n.scope
                      ? n
                      : null
            );
        }
        register(...e) {
            return e.map((r) => {
                const n = "blotName" in r,
                    s = "attrName" in r;
                if (!n && !s) throw new Wr("Invalid definition");
                if (n && r.blotName === "abstract")
                    throw new Wr("Cannot register abstract class");
                const i = n ? r.blotName : s ? r.attrName : void 0;
                return (
                    (this.types[i] = r),
                    s
                        ? typeof r.keyName == "string" &&
                          (this.attributes[r.keyName] = r)
                        : n &&
                          (r.className && (this.classes[r.className] = r),
                          r.tagName &&
                              (Array.isArray(r.tagName)
                                  ? (r.tagName = r.tagName.map((o) =>
                                        o.toUpperCase(),
                                    ))
                                  : (r.tagName = r.tagName.toUpperCase()),
                              (Array.isArray(r.tagName)
                                  ? r.tagName
                                  : [r.tagName]
                              ).forEach((o) => {
                                  (this.tags[o] == null ||
                                      r.className == null) &&
                                      (this.tags[o] = r);
                              }))),
                    r
                );
            });
        }
    };
    ou.blots = new WeakMap();
    let Yr = ou;
    function m0(t, e) {
        return (t.getAttribute("class") || "")
            .split(/\s+/)
            .filter((r) => r.indexOf(`${e}-`) === 0);
    }
    class Fv extends yt {
        static keys(e) {
            return (e.getAttribute("class") || "")
                .split(/\s+/)
                .map((r) => r.split("-").slice(0, -1).join("-"));
        }
        add(e, r) {
            return this.canAdd(e, r)
                ? (this.remove(e), e.classList.add(`${this.keyName}-${r}`), !0)
                : !1;
        }
        remove(e) {
            (m0(e, this.keyName).forEach((r) => {
                e.classList.remove(r);
            }),
                e.classList.length === 0 && e.removeAttribute("class"));
        }
        value(e) {
            const r = (m0(e, this.keyName)[0] || "").slice(
                this.keyName.length + 1,
            );
            return this.canAdd(e, r) ? r : "";
        }
    }
    const lt = Fv;
    function wo(t) {
        const e = t.split("-"),
            r = e
                .slice(1)
                .map((n) => n[0].toUpperCase() + n.slice(1))
                .join("");
        return e[0] + r;
    }
    class Hv extends yt {
        static keys(e) {
            return (e.getAttribute("style") || "")
                .split(";")
                .map((r) => r.split(":")[0].trim());
        }
        add(e, r) {
            return this.canAdd(e, r)
                ? ((e.style[wo(this.keyName)] = r), !0)
                : !1;
        }
        remove(e) {
            ((e.style[wo(this.keyName)] = ""),
                e.getAttribute("style") || e.removeAttribute("style"));
        }
        value(e) {
            const r = e.style[wo(this.keyName)];
            return this.canAdd(e, r) ? r : "";
        }
    }
    const Vt = Hv;
    class zv {
        constructor(e) {
            ((this.attributes = {}), (this.domNode = e), this.build());
        }
        attribute(e, r) {
            r
                ? e.add(this.domNode, r) &&
                  (e.value(this.domNode) != null
                      ? (this.attributes[e.attrName] = e)
                      : delete this.attributes[e.attrName])
                : (e.remove(this.domNode), delete this.attributes[e.attrName]);
        }
        build() {
            this.attributes = {};
            const e = Yr.find(this.domNode);
            if (e == null) return;
            const r = yt.keys(this.domNode),
                n = lt.keys(this.domNode),
                s = Vt.keys(this.domNode);
            r.concat(n)
                .concat(s)
                .forEach((i) => {
                    const o = e.scroll.query(i, K.ATTRIBUTE);
                    o instanceof yt && (this.attributes[o.attrName] = o);
                });
        }
        copy(e) {
            Object.keys(this.attributes).forEach((r) => {
                const n = this.attributes[r].value(this.domNode);
                e.format(r, n);
            });
        }
        move(e) {
            (this.copy(e),
                Object.keys(this.attributes).forEach((r) => {
                    this.attributes[r].remove(this.domNode);
                }),
                (this.attributes = {}));
        }
        values() {
            return Object.keys(this.attributes).reduce(
                (e, r) => ((e[r] = this.attributes[r].value(this.domNode)), e),
                {},
            );
        }
    }
    const mi = zv,
        au = class {
            constructor(e, r) {
                ((this.scroll = e),
                    (this.domNode = r),
                    Yr.blots.set(r, this),
                    (this.prev = null),
                    (this.next = null));
            }
            static create(e) {
                if (this.tagName == null)
                    throw new Wr("Blot definition missing tagName");
                let r, n;
                return (
                    Array.isArray(this.tagName)
                        ? (typeof e == "string"
                              ? ((n = e.toUpperCase()),
                                parseInt(n, 10).toString() === n &&
                                    (n = parseInt(n, 10)))
                              : typeof e == "number" && (n = e),
                          typeof n == "number"
                              ? (r = document.createElement(
                                    this.tagName[n - 1],
                                ))
                              : n && this.tagName.indexOf(n) > -1
                                ? (r = document.createElement(n))
                                : (r = document.createElement(this.tagName[0])))
                        : (r = document.createElement(this.tagName)),
                    this.className && r.classList.add(this.className),
                    r
                );
            }
            get statics() {
                return this.constructor;
            }
            attach() {}
            clone() {
                const e = this.domNode.cloneNode(!1);
                return this.scroll.create(e);
            }
            detach() {
                (this.parent != null && this.parent.removeChild(this),
                    Yr.blots.delete(this.domNode));
            }
            deleteAt(e, r) {
                this.isolate(e, r).remove();
            }
            formatAt(e, r, n, s) {
                const i = this.isolate(e, r);
                if (this.scroll.query(n, K.BLOT) != null && s) i.wrap(n, s);
                else if (this.scroll.query(n, K.ATTRIBUTE) != null) {
                    const o = this.scroll.create(this.statics.scope);
                    (i.wrap(o), o.format(n, s));
                }
            }
            insertAt(e, r, n) {
                const s =
                        n == null
                            ? this.scroll.create("text", r)
                            : this.scroll.create(r, n),
                    i = this.split(e);
                this.parent.insertBefore(s, i || void 0);
            }
            isolate(e, r) {
                const n = this.split(e);
                if (n == null) throw new Error("Attempt to isolate at end");
                return (n.split(r), n);
            }
            length() {
                return 1;
            }
            offset(e = this.parent) {
                return this.parent == null || this === e
                    ? 0
                    : this.parent.children.offset(this) + this.parent.offset(e);
            }
            optimize(e) {
                this.statics.requiredContainer &&
                    !(this.parent instanceof this.statics.requiredContainer) &&
                    this.wrap(this.statics.requiredContainer.blotName);
            }
            remove() {
                (this.domNode.parentNode != null &&
                    this.domNode.parentNode.removeChild(this.domNode),
                    this.detach());
            }
            replaceWith(e, r) {
                const n = typeof e == "string" ? this.scroll.create(e, r) : e;
                return (
                    this.parent != null &&
                        (this.parent.insertBefore(n, this.next || void 0),
                        this.remove()),
                    n
                );
            }
            split(e, r) {
                return e === 0 ? this : this.next;
            }
            update(e, r) {}
            wrap(e, r) {
                const n = typeof e == "string" ? this.scroll.create(e, r) : e;
                if (
                    (this.parent != null &&
                        this.parent.insertBefore(n, this.next || void 0),
                    typeof n.appendChild != "function")
                )
                    throw new Wr(`Cannot wrap ${e}`);
                return (n.appendChild(this), n);
            }
        };
    au.blotName = "abstract";
    let lu = au;
    const fu = class extends lu {
        static value(e) {
            return !0;
        }
        index(e, r) {
            return this.domNode === e ||
                this.domNode.compareDocumentPosition(e) &
                    Node.DOCUMENT_POSITION_CONTAINED_BY
                ? Math.min(r, 1)
                : -1;
        }
        position(e, r) {
            let n = Array.from(this.parent.domNode.childNodes).indexOf(
                this.domNode,
            );
            return (e > 0 && (n += 1), [this.parent.domNode, n]);
        }
        value() {
            return {
                [this.statics.blotName]: this.statics.value(this.domNode) || !0,
            };
        }
    };
    fu.scope = K.INLINE_BLOT;
    let Kv = fu;
    const qe = Kv;
    class Wv {
        constructor() {
            ((this.head = null), (this.tail = null), (this.length = 0));
        }
        append(...e) {
            if ((this.insertBefore(e[0], null), e.length > 1)) {
                const r = e.slice(1);
                this.append(...r);
            }
        }
        at(e) {
            const r = this.iterator();
            let n = r();
            for (; n && e > 0; ) ((e -= 1), (n = r()));
            return n;
        }
        contains(e) {
            const r = this.iterator();
            let n = r();
            for (; n; ) {
                if (n === e) return !0;
                n = r();
            }
            return !1;
        }
        indexOf(e) {
            const r = this.iterator();
            let n = r(),
                s = 0;
            for (; n; ) {
                if (n === e) return s;
                ((s += 1), (n = r()));
            }
            return -1;
        }
        insertBefore(e, r) {
            e != null &&
                (this.remove(e),
                (e.next = r),
                r != null
                    ? ((e.prev = r.prev),
                      r.prev != null && (r.prev.next = e),
                      (r.prev = e),
                      r === this.head && (this.head = e))
                    : this.tail != null
                      ? ((this.tail.next = e),
                        (e.prev = this.tail),
                        (this.tail = e))
                      : ((e.prev = null), (this.head = this.tail = e)),
                (this.length += 1));
        }
        offset(e) {
            let r = 0,
                n = this.head;
            for (; n != null; ) {
                if (n === e) return r;
                ((r += n.length()), (n = n.next));
            }
            return -1;
        }
        remove(e) {
            this.contains(e) &&
                (e.prev != null && (e.prev.next = e.next),
                e.next != null && (e.next.prev = e.prev),
                e === this.head && (this.head = e.next),
                e === this.tail && (this.tail = e.prev),
                (this.length -= 1));
        }
        iterator(e = this.head) {
            return () => {
                const r = e;
                return (e != null && (e = e.next), r);
            };
        }
        find(e, r = !1) {
            const n = this.iterator();
            let s = n();
            for (; s; ) {
                const i = s.length();
                if (
                    e < i ||
                    (r && e === i && (s.next == null || s.next.length() !== 0))
                )
                    return [s, e];
                ((e -= i), (s = n()));
            }
            return [null, 0];
        }
        forEach(e) {
            const r = this.iterator();
            let n = r();
            for (; n; ) (e(n), (n = r()));
        }
        forEachAt(e, r, n) {
            if (r <= 0) return;
            const [s, i] = this.find(e);
            let o = e - i;
            const a = this.iterator(s);
            let l = a();
            for (; l && o < e + r; ) {
                const f = l.length();
                (e > o
                    ? n(l, e - o, Math.min(r, o + f - e))
                    : n(l, 0, Math.min(f, e + r - o)),
                    (o += f),
                    (l = a()));
            }
        }
        map(e) {
            return this.reduce((r, n) => (r.push(e(n)), r), []);
        }
        reduce(e, r) {
            const n = this.iterator();
            let s = n();
            for (; s; ) ((r = e(r, s)), (s = n()));
            return r;
        }
    }
    function b0(t, e) {
        const r = e.find(t);
        if (r) return r;
        try {
            return e.create(t);
        } catch {
            const n = e.create(K.INLINE);
            return (
                Array.from(t.childNodes).forEach((s) => {
                    n.domNode.appendChild(s);
                }),
                t.parentNode && t.parentNode.replaceChild(n.domNode, t),
                n.attach(),
                n
            );
        }
    }
    const cu = class Bt extends lu {
        constructor(e, r) {
            (super(e, r), (this.uiNode = null), this.build());
        }
        appendChild(e) {
            this.insertBefore(e);
        }
        attach() {
            (super.attach(),
                this.children.forEach((e) => {
                    e.attach();
                }));
        }
        attachUI(e) {
            (this.uiNode != null && this.uiNode.remove(),
                (this.uiNode = e),
                Bt.uiClass && this.uiNode.classList.add(Bt.uiClass),
                this.uiNode.setAttribute("contenteditable", "false"),
                this.domNode.insertBefore(
                    this.uiNode,
                    this.domNode.firstChild,
                ));
        }
        build() {
            ((this.children = new Wv()),
                Array.from(this.domNode.childNodes)
                    .filter((e) => e !== this.uiNode)
                    .reverse()
                    .forEach((e) => {
                        try {
                            const r = b0(e, this.scroll);
                            this.insertBefore(r, this.children.head || void 0);
                        } catch (r) {
                            if (r instanceof Wr) return;
                            throw r;
                        }
                    }));
        }
        deleteAt(e, r) {
            if (e === 0 && r === this.length()) return this.remove();
            this.children.forEachAt(e, r, (n, s, i) => {
                n.deleteAt(s, i);
            });
        }
        descendant(e, r = 0) {
            const [n, s] = this.children.find(r);
            return (e.blotName == null && e(n)) ||
                (e.blotName != null && n instanceof e)
                ? [n, s]
                : n instanceof Bt
                  ? n.descendant(e, s)
                  : [null, -1];
        }
        descendants(e, r = 0, n = Number.MAX_VALUE) {
            let s = [],
                i = n;
            return (
                this.children.forEachAt(r, n, (o, a, l) => {
                    (((e.blotName == null && e(o)) ||
                        (e.blotName != null && o instanceof e)) &&
                        s.push(o),
                        o instanceof Bt &&
                            (s = s.concat(o.descendants(e, a, i))),
                        (i -= l));
                }),
                s
            );
        }
        detach() {
            (this.children.forEach((e) => {
                e.detach();
            }),
                super.detach());
        }
        enforceAllowedChildren() {
            let e = !1;
            this.children.forEach((r) => {
                e ||
                    this.statics.allowedChildren.some((n) => r instanceof n) ||
                    (r.statics.scope === K.BLOCK_BLOT
                        ? (r.next != null && this.splitAfter(r),
                          r.prev != null && this.splitAfter(r.prev),
                          r.parent.unwrap(),
                          (e = !0))
                        : r instanceof Bt
                          ? r.unwrap()
                          : r.remove());
            });
        }
        formatAt(e, r, n, s) {
            this.children.forEachAt(e, r, (i, o, a) => {
                i.formatAt(o, a, n, s);
            });
        }
        insertAt(e, r, n) {
            const [s, i] = this.children.find(e);
            if (s) s.insertAt(i, r, n);
            else {
                const o =
                    n == null
                        ? this.scroll.create("text", r)
                        : this.scroll.create(r, n);
                this.appendChild(o);
            }
        }
        insertBefore(e, r) {
            e.parent != null && e.parent.children.remove(e);
            let n = null;
            (this.children.insertBefore(e, r || null),
                (e.parent = this),
                r != null && (n = r.domNode),
                (this.domNode.parentNode !== e.domNode ||
                    this.domNode.nextSibling !== n) &&
                    this.domNode.insertBefore(e.domNode, n),
                e.attach());
        }
        length() {
            return this.children.reduce((e, r) => e + r.length(), 0);
        }
        moveChildren(e, r) {
            this.children.forEach((n) => {
                e.insertBefore(n, r);
            });
        }
        optimize(e) {
            if (
                (super.optimize(e),
                this.enforceAllowedChildren(),
                this.uiNode != null &&
                    this.uiNode !== this.domNode.firstChild &&
                    this.domNode.insertBefore(
                        this.uiNode,
                        this.domNode.firstChild,
                    ),
                this.children.length === 0)
            )
                if (this.statics.defaultChild != null) {
                    const r = this.scroll.create(
                        this.statics.defaultChild.blotName,
                    );
                    this.appendChild(r);
                } else this.remove();
        }
        path(e, r = !1) {
            const [n, s] = this.children.find(e, r),
                i = [[this, e]];
            return n instanceof Bt
                ? i.concat(n.path(s, r))
                : (n != null && i.push([n, s]), i);
        }
        removeChild(e) {
            this.children.remove(e);
        }
        replaceWith(e, r) {
            const n = typeof e == "string" ? this.scroll.create(e, r) : e;
            return (
                n instanceof Bt && this.moveChildren(n),
                super.replaceWith(n)
            );
        }
        split(e, r = !1) {
            if (!r) {
                if (e === 0) return this;
                if (e === this.length()) return this.next;
            }
            const n = this.clone();
            return (
                this.parent && this.parent.insertBefore(n, this.next || void 0),
                this.children.forEachAt(e, this.length(), (s, i, o) => {
                    const a = s.split(i, r);
                    a != null && n.appendChild(a);
                }),
                n
            );
        }
        splitAfter(e) {
            const r = this.clone();
            for (; e.next != null; ) r.appendChild(e.next);
            return (
                this.parent && this.parent.insertBefore(r, this.next || void 0),
                r
            );
        }
        unwrap() {
            (this.parent && this.moveChildren(this.parent, this.next || void 0),
                this.remove());
        }
        update(e, r) {
            const n = [],
                s = [];
            (e.forEach((i) => {
                i.target === this.domNode &&
                    i.type === "childList" &&
                    (n.push(...i.addedNodes), s.push(...i.removedNodes));
            }),
                s.forEach((i) => {
                    if (
                        i.parentNode != null &&
                        i.tagName !== "IFRAME" &&
                        document.body.compareDocumentPosition(i) &
                            Node.DOCUMENT_POSITION_CONTAINED_BY
                    )
                        return;
                    const o = this.scroll.find(i);
                    o != null &&
                        (o.domNode.parentNode == null ||
                            o.domNode.parentNode === this.domNode) &&
                        o.detach();
                }),
                n
                    .filter(
                        (i) =>
                            i.parentNode === this.domNode && i !== this.uiNode,
                    )
                    .sort((i, o) =>
                        i === o
                            ? 0
                            : i.compareDocumentPosition(o) &
                                Node.DOCUMENT_POSITION_FOLLOWING
                              ? 1
                              : -1,
                    )
                    .forEach((i) => {
                        let o = null;
                        i.nextSibling != null &&
                            (o = this.scroll.find(i.nextSibling));
                        const a = b0(i, this.scroll);
                        (a.next !== o || a.next == null) &&
                            (a.parent != null && a.parent.removeChild(this),
                            this.insertBefore(a, o || void 0));
                    }),
                this.enforceAllowedChildren());
        }
    };
    cu.uiClass = "";
    let Vv = cu;
    const nt = Vv;
    function Gv(t, e) {
        if (Object.keys(t).length !== Object.keys(e).length) return !1;
        for (const r in t) if (t[r] !== e[r]) return !1;
        return !0;
    }
    const Dr = class jr extends nt {
        static create(e) {
            return super.create(e);
        }
        static formats(e, r) {
            const n = r.query(jr.blotName);
            if (!(n != null && e.tagName === n.tagName)) {
                if (typeof this.tagName == "string") return !0;
                if (Array.isArray(this.tagName)) return e.tagName.toLowerCase();
            }
        }
        constructor(e, r) {
            (super(e, r), (this.attributes = new mi(this.domNode)));
        }
        format(e, r) {
            if (e === this.statics.blotName && !r)
                (this.children.forEach((n) => {
                    (n instanceof jr || (n = n.wrap(jr.blotName, !0)),
                        this.attributes.copy(n));
                }),
                    this.unwrap());
            else {
                const n = this.scroll.query(e, K.INLINE);
                if (n == null) return;
                n instanceof yt
                    ? this.attributes.attribute(n, r)
                    : r &&
                      (e !== this.statics.blotName ||
                          this.formats()[e] !== r) &&
                      this.replaceWith(e, r);
            }
        }
        formats() {
            const e = this.attributes.values(),
                r = this.statics.formats(this.domNode, this.scroll);
            return (r != null && (e[this.statics.blotName] = r), e);
        }
        formatAt(e, r, n, s) {
            this.formats()[n] != null || this.scroll.query(n, K.ATTRIBUTE)
                ? this.isolate(e, r).format(n, s)
                : super.formatAt(e, r, n, s);
        }
        optimize(e) {
            super.optimize(e);
            const r = this.formats();
            if (Object.keys(r).length === 0) return this.unwrap();
            const n = this.next;
            n instanceof jr &&
                n.prev === this &&
                Gv(r, n.formats()) &&
                (n.moveChildren(this), n.remove());
        }
        replaceWith(e, r) {
            const n = super.replaceWith(e, r);
            return (this.attributes.copy(n), n);
        }
        update(e, r) {
            (super.update(e, r),
                e.some(
                    (n) => n.target === this.domNode && n.type === "attributes",
                ) && this.attributes.build());
        }
        wrap(e, r) {
            const n = super.wrap(e, r);
            return (n instanceof jr && this.attributes.move(n), n);
        }
    };
    ((Dr.allowedChildren = [Dr, qe]),
        (Dr.blotName = "inline"),
        (Dr.scope = K.INLINE_BLOT),
        (Dr.tagName = "SPAN"));
    let Zv = Dr;
    const Ya = Zv,
        Ur = class oa extends nt {
            static create(e) {
                return super.create(e);
            }
            static formats(e, r) {
                const n = r.query(oa.blotName);
                if (!(n != null && e.tagName === n.tagName)) {
                    if (typeof this.tagName == "string") return !0;
                    if (Array.isArray(this.tagName))
                        return e.tagName.toLowerCase();
                }
            }
            constructor(e, r) {
                (super(e, r), (this.attributes = new mi(this.domNode)));
            }
            format(e, r) {
                const n = this.scroll.query(e, K.BLOCK);
                n != null &&
                    (n instanceof yt
                        ? this.attributes.attribute(n, r)
                        : e === this.statics.blotName && !r
                          ? this.replaceWith(oa.blotName)
                          : r &&
                            (e !== this.statics.blotName ||
                                this.formats()[e] !== r) &&
                            this.replaceWith(e, r));
            }
            formats() {
                const e = this.attributes.values(),
                    r = this.statics.formats(this.domNode, this.scroll);
                return (r != null && (e[this.statics.blotName] = r), e);
            }
            formatAt(e, r, n, s) {
                this.scroll.query(n, K.BLOCK) != null
                    ? this.format(n, s)
                    : super.formatAt(e, r, n, s);
            }
            insertAt(e, r, n) {
                if (n == null || this.scroll.query(r, K.INLINE) != null)
                    super.insertAt(e, r, n);
                else {
                    const s = this.split(e);
                    if (s != null) {
                        const i = this.scroll.create(r, n);
                        s.parent.insertBefore(i, s);
                    } else
                        throw new Error(
                            "Attempt to insertAt after block boundaries",
                        );
                }
            }
            replaceWith(e, r) {
                const n = super.replaceWith(e, r);
                return (this.attributes.copy(n), n);
            }
            update(e, r) {
                (super.update(e, r),
                    e.some(
                        (n) =>
                            n.target === this.domNode &&
                            n.type === "attributes",
                    ) && this.attributes.build());
            }
        };
    ((Ur.blotName = "block"),
        (Ur.scope = K.BLOCK_BLOT),
        (Ur.tagName = "P"),
        (Ur.allowedChildren = [Ya, Ur, qe]));
    let Xv = Ur;
    const Wn = Xv,
        aa = class extends nt {
            checkMerge() {
                return (
                    this.next !== null &&
                    this.next.statics.blotName === this.statics.blotName
                );
            }
            deleteAt(e, r) {
                (super.deleteAt(e, r), this.enforceAllowedChildren());
            }
            formatAt(e, r, n, s) {
                (super.formatAt(e, r, n, s), this.enforceAllowedChildren());
            }
            insertAt(e, r, n) {
                (super.insertAt(e, r, n), this.enforceAllowedChildren());
            }
            optimize(e) {
                (super.optimize(e),
                    this.children.length > 0 &&
                        this.next != null &&
                        this.checkMerge() &&
                        (this.next.moveChildren(this), this.next.remove()));
            }
        };
    ((aa.blotName = "container"), (aa.scope = K.BLOCK_BLOT));
    let Jv = aa;
    const bi = Jv;
    class Yv extends qe {
        static formats(e, r) {}
        format(e, r) {
            super.formatAt(0, this.length(), e, r);
        }
        formatAt(e, r, n, s) {
            e === 0 && r === this.length()
                ? this.format(n, s)
                : super.formatAt(e, r, n, s);
        }
        formats() {
            return this.statics.formats(this.domNode, this.scroll);
        }
    }
    const He = Yv,
        Qv = {
            attributes: !0,
            characterData: !0,
            characterDataOldValue: !0,
            childList: !0,
            subtree: !0,
        },
        ew = 100,
        Fr = class extends nt {
            constructor(e, r) {
                (super(null, r),
                    (this.registry = e),
                    (this.scroll = this),
                    this.build(),
                    (this.observer = new MutationObserver((n) => {
                        this.update(n);
                    })),
                    this.observer.observe(this.domNode, Qv),
                    this.attach());
            }
            create(e, r) {
                return this.registry.create(this, e, r);
            }
            find(e, r = !1) {
                const n = this.registry.find(e, r);
                return n
                    ? n.scroll === this
                        ? n
                        : r
                          ? this.find(n.scroll.domNode.parentNode, !0)
                          : null
                    : null;
            }
            query(e, r = K.ANY) {
                return this.registry.query(e, r);
            }
            register(...e) {
                return this.registry.register(...e);
            }
            build() {
                this.scroll != null && super.build();
            }
            detach() {
                (super.detach(), this.observer.disconnect());
            }
            deleteAt(e, r) {
                (this.update(),
                    e === 0 && r === this.length()
                        ? this.children.forEach((n) => {
                              n.remove();
                          })
                        : super.deleteAt(e, r));
            }
            formatAt(e, r, n, s) {
                (this.update(), super.formatAt(e, r, n, s));
            }
            insertAt(e, r, n) {
                (this.update(), super.insertAt(e, r, n));
            }
            optimize(e = [], r = {}) {
                super.optimize(r);
                const n = r.mutationsMap || new WeakMap();
                let s = Array.from(this.observer.takeRecords());
                for (; s.length > 0; ) e.push(s.pop());
                const i = (l, f = !0) => {
                        l == null ||
                            l === this ||
                            (l.domNode.parentNode != null &&
                                (n.has(l.domNode) || n.set(l.domNode, []),
                                f && i(l.parent)));
                    },
                    o = (l) => {
                        n.has(l.domNode) &&
                            (l instanceof nt && l.children.forEach(o),
                            n.delete(l.domNode),
                            l.optimize(r));
                    };
                let a = e;
                for (let l = 0; a.length > 0; l += 1) {
                    if (l >= ew)
                        throw new Error(
                            "[Parchment] Maximum optimize iterations reached",
                        );
                    for (
                        a.forEach((f) => {
                            const u = this.find(f.target, !0);
                            u != null &&
                                (u.domNode === f.target &&
                                    (f.type === "childList"
                                        ? (i(this.find(f.previousSibling, !1)),
                                          Array.from(f.addedNodes).forEach(
                                              (d) => {
                                                  const h = this.find(d, !1);
                                                  (i(h, !1),
                                                      h instanceof nt &&
                                                          h.children.forEach(
                                                              (g) => {
                                                                  i(g, !1);
                                                              },
                                                          ));
                                              },
                                          ))
                                        : f.type === "attributes" && i(u.prev)),
                                i(u));
                        }),
                            this.children.forEach(o),
                            a = Array.from(this.observer.takeRecords()),
                            s = a.slice();
                        s.length > 0;
                    )
                        e.push(s.pop());
                }
            }
            update(e, r = {}) {
                e = e || this.observer.takeRecords();
                const n = new WeakMap();
                (e
                    .map((s) => {
                        const i = this.find(s.target, !0);
                        return i == null
                            ? null
                            : n.has(i.domNode)
                              ? (n.get(i.domNode).push(s), null)
                              : (n.set(i.domNode, [s]), i);
                    })
                    .forEach((s) => {
                        s != null &&
                            s !== this &&
                            n.has(s.domNode) &&
                            s.update(n.get(s.domNode) || [], r);
                    }),
                    (r.mutationsMap = n),
                    n.has(this.domNode) && super.update(n.get(this.domNode), r),
                    this.optimize(e, r));
            }
        };
    ((Fr.blotName = "scroll"),
        (Fr.defaultChild = Wn),
        (Fr.allowedChildren = [Wn, bi]),
        (Fr.scope = K.BLOCK_BLOT),
        (Fr.tagName = "DIV"));
    let tw = Fr;
    const Qa = tw,
        la = class uu extends qe {
            static create(e) {
                return document.createTextNode(e);
            }
            static value(e) {
                return e.data;
            }
            constructor(e, r) {
                (super(e, r), (this.text = this.statics.value(this.domNode)));
            }
            deleteAt(e, r) {
                this.domNode.data = this.text =
                    this.text.slice(0, e) + this.text.slice(e + r);
            }
            index(e, r) {
                return this.domNode === e ? r : -1;
            }
            insertAt(e, r, n) {
                n == null
                    ? ((this.text =
                          this.text.slice(0, e) + r + this.text.slice(e)),
                      (this.domNode.data = this.text))
                    : super.insertAt(e, r, n);
            }
            length() {
                return this.text.length;
            }
            optimize(e) {
                (super.optimize(e),
                    (this.text = this.statics.value(this.domNode)),
                    this.text.length === 0
                        ? this.remove()
                        : this.next instanceof uu &&
                          this.next.prev === this &&
                          (this.insertAt(this.length(), this.next.value()),
                          this.next.remove()));
            }
            position(e, r = !1) {
                return [this.domNode, e];
            }
            split(e, r = !1) {
                if (!r) {
                    if (e === 0) return this;
                    if (e === this.length()) return this.next;
                }
                const n = this.scroll.create(this.domNode.splitText(e));
                return (
                    this.parent.insertBefore(n, this.next || void 0),
                    (this.text = this.statics.value(this.domNode)),
                    n
                );
            }
            update(e, r) {
                e.some(
                    (n) =>
                        n.type === "characterData" && n.target === this.domNode,
                ) && (this.text = this.statics.value(this.domNode));
            }
            value() {
                return this.text;
            }
        };
    ((la.blotName = "text"), (la.scope = K.INLINE_BLOT));
    let rw = la;
    const ei = rw,
        nw = Object.freeze(
            Object.defineProperty(
                {
                    __proto__: null,
                    Attributor: yt,
                    AttributorStore: mi,
                    BlockBlot: Wn,
                    ClassAttributor: lt,
                    ContainerBlot: bi,
                    EmbedBlot: He,
                    InlineBlot: Ya,
                    LeafBlot: qe,
                    ParentBlot: nt,
                    Registry: Yr,
                    Scope: K,
                    ScrollBlot: Qa,
                    StyleAttributor: Vt,
                    TextBlot: ei,
                },
                Symbol.toStringTag,
                { value: "Module" },
            ),
        );
    var $t =
        typeof globalThis < "u"
            ? globalThis
            : typeof window < "u"
              ? window
              : typeof global < "u"
                ? global
                : typeof self < "u"
                  ? self
                  : {};
    function hu(t) {
        return t &&
            t.__esModule &&
            Object.prototype.hasOwnProperty.call(t, "default")
            ? t.default
            : t;
    }
    var Ls = { exports: {} },
        Eo,
        y0;
    function sw() {
        if (y0) return Eo;
        y0 = 1;
        var t = -1,
            e = 1,
            r = 0;
        function n(w, R, T, k, M) {
            if (w === R) return w ? [[r, w]] : [];
            if (T != null) {
                var m = de(w, R, T);
                if (m) return m;
            }
            var A = a(w, R),
                O = w.substring(0, A);
            ((w = w.substring(A)), (R = R.substring(A)), (A = f(w, R)));
            var B = w.substring(w.length - A);
            ((w = w.substring(0, w.length - A)),
                (R = R.substring(0, R.length - A)));
            var I = s(w, R);
            return (
                O && I.unshift([r, O]),
                B && I.push([r, B]),
                S(I, M),
                k && d(I),
                I
            );
        }
        function s(w, R) {
            var T;
            if (!w) return [[e, R]];
            if (!R) return [[t, w]];
            var k = w.length > R.length ? w : R,
                M = w.length > R.length ? R : w,
                m = k.indexOf(M);
            if (m !== -1)
                return (
                    (T = [
                        [e, k.substring(0, m)],
                        [r, M],
                        [e, k.substring(m + M.length)],
                    ]),
                    w.length > R.length && (T[0][0] = T[2][0] = t),
                    T
                );
            if (M.length === 1)
                return [
                    [t, w],
                    [e, R],
                ];
            var A = u(w, R);
            if (A) {
                var O = A[0],
                    B = A[1],
                    I = A[2],
                    $ = A[3],
                    D = A[4],
                    V = n(O, I),
                    Z = n(B, $);
                return V.concat([[r, D]], Z);
            }
            return i(w, R);
        }
        function i(w, R) {
            for (
                var T = w.length,
                    k = R.length,
                    M = Math.ceil((T + k) / 2),
                    m = M,
                    A = 2 * M,
                    O = new Array(A),
                    B = new Array(A),
                    I = 0;
                I < A;
                I++
            )
                ((O[I] = -1), (B[I] = -1));
            ((O[m + 1] = 0), (B[m + 1] = 0));
            for (
                var $ = T - k,
                    D = $ % 2 !== 0,
                    V = 0,
                    Z = 0,
                    U = 0,
                    W = 0,
                    Y = 0;
                Y < M;
                Y++
            ) {
                for (var X = -Y + V; X <= Y - Z; X += 2) {
                    var ne = m + X,
                        se;
                    X === -Y || (X !== Y && O[ne - 1] < O[ne + 1])
                        ? (se = O[ne + 1])
                        : (se = O[ne - 1] + 1);
                    for (
                        var ie = se - X;
                        se < T && ie < k && w.charAt(se) === R.charAt(ie);
                    )
                        (se++, ie++);
                    if (((O[ne] = se), se > T)) Z += 2;
                    else if (ie > k) V += 2;
                    else if (D) {
                        var ee = m + $ - X;
                        if (ee >= 0 && ee < A && B[ee] !== -1) {
                            var le = T - B[ee];
                            if (se >= le) return o(w, R, se, ie);
                        }
                    }
                }
                for (var me = -Y + U; me <= Y - W; me += 2) {
                    var ee = m + me,
                        le;
                    me === -Y || (me !== Y && B[ee - 1] < B[ee + 1])
                        ? (le = B[ee + 1])
                        : (le = B[ee - 1] + 1);
                    for (
                        var ue = le - me;
                        le < T &&
                        ue < k &&
                        w.charAt(T - le - 1) === R.charAt(k - ue - 1);
                    )
                        (le++, ue++);
                    if (((B[ee] = le), le > T)) W += 2;
                    else if (ue > k) U += 2;
                    else if (!D) {
                        var ne = m + $ - me;
                        if (ne >= 0 && ne < A && O[ne] !== -1) {
                            var se = O[ne],
                                ie = m + se - ne;
                            if (((le = T - le), se >= le))
                                return o(w, R, se, ie);
                        }
                    }
                }
            }
            return [
                [t, w],
                [e, R],
            ];
        }
        function o(w, R, T, k) {
            var M = w.substring(0, T),
                m = R.substring(0, k),
                A = w.substring(T),
                O = R.substring(k),
                B = n(M, m),
                I = n(A, O);
            return B.concat(I);
        }
        function a(w, R) {
            if (!w || !R || w.charAt(0) !== R.charAt(0)) return 0;
            for (
                var T = 0, k = Math.min(w.length, R.length), M = k, m = 0;
                T < M;
            )
                (w.substring(m, M) == R.substring(m, M)
                    ? ((T = M), (m = T))
                    : (k = M),
                    (M = Math.floor((k - T) / 2 + T)));
            return (N(w.charCodeAt(M - 1)) && M--, M);
        }
        function l(w, R) {
            var T = w.length,
                k = R.length;
            if (T == 0 || k == 0) return 0;
            T > k ? (w = w.substring(T - k)) : T < k && (R = R.substring(0, T));
            var M = Math.min(T, k);
            if (w == R) return M;
            for (var m = 0, A = 1; ; ) {
                var O = w.substring(M - A),
                    B = R.indexOf(O);
                if (B == -1) return m;
                ((A += B),
                    (B == 0 || w.substring(M - A) == R.substring(0, A)) &&
                        ((m = A), A++));
            }
        }
        function f(w, R) {
            if (!w || !R || w.slice(-1) !== R.slice(-1)) return 0;
            for (
                var T = 0, k = Math.min(w.length, R.length), M = k, m = 0;
                T < M;
            )
                (w.substring(w.length - M, w.length - m) ==
                R.substring(R.length - M, R.length - m)
                    ? ((T = M), (m = T))
                    : (k = M),
                    (M = Math.floor((k - T) / 2 + T)));
            return (P(w.charCodeAt(w.length - M)) && M--, M);
        }
        function u(w, R) {
            var T = w.length > R.length ? w : R,
                k = w.length > R.length ? R : w;
            if (T.length < 4 || k.length * 2 < T.length) return null;
            function M(Z, U, W) {
                for (
                    var Y = Z.substring(W, W + Math.floor(Z.length / 4)),
                        X = -1,
                        ne = "",
                        se,
                        ie,
                        ee,
                        le;
                    (X = U.indexOf(Y, X + 1)) !== -1;
                ) {
                    var me = a(Z.substring(W), U.substring(X)),
                        ue = f(Z.substring(0, W), U.substring(0, X));
                    ne.length < ue + me &&
                        ((ne = U.substring(X - ue, X) + U.substring(X, X + me)),
                        (se = Z.substring(0, W - ue)),
                        (ie = Z.substring(W + me)),
                        (ee = U.substring(0, X - ue)),
                        (le = U.substring(X + me)));
                }
                return ne.length * 2 >= Z.length ? [se, ie, ee, le, ne] : null;
            }
            var m = M(T, k, Math.ceil(T.length / 4)),
                A = M(T, k, Math.ceil(T.length / 2)),
                O;
            if (!m && !A) return null;
            A
                ? m
                    ? (O = m[4].length > A[4].length ? m : A)
                    : (O = A)
                : (O = m);
            var B, I, $, D;
            w.length > R.length
                ? ((B = O[0]), (I = O[1]), ($ = O[2]), (D = O[3]))
                : (($ = O[0]), (D = O[1]), (B = O[2]), (I = O[3]));
            var V = O[4];
            return [B, I, $, D, V];
        }
        function d(w) {
            for (
                var R = !1,
                    T = [],
                    k = 0,
                    M = null,
                    m = 0,
                    A = 0,
                    O = 0,
                    B = 0,
                    I = 0;
                m < w.length;
            )
                (w[m][0] == r
                    ? ((T[k++] = m),
                      (A = B),
                      (O = I),
                      (B = 0),
                      (I = 0),
                      (M = w[m][1]))
                    : (w[m][0] == e
                          ? (B += w[m][1].length)
                          : (I += w[m][1].length),
                      M &&
                          M.length <= Math.max(A, O) &&
                          M.length <= Math.max(B, I) &&
                          (w.splice(T[k - 1], 0, [t, M]),
                          (w[T[k - 1] + 1][0] = e),
                          k--,
                          k--,
                          (m = k > 0 ? T[k - 1] : -1),
                          (A = 0),
                          (O = 0),
                          (B = 0),
                          (I = 0),
                          (M = null),
                          (R = !0))),
                    m++);
            for (R && S(w), y(w), m = 1; m < w.length; ) {
                if (w[m - 1][0] == t && w[m][0] == e) {
                    var $ = w[m - 1][1],
                        D = w[m][1],
                        V = l($, D),
                        Z = l(D, $);
                    (V >= Z
                        ? (V >= $.length / 2 || V >= D.length / 2) &&
                          (w.splice(m, 0, [r, D.substring(0, V)]),
                          (w[m - 1][1] = $.substring(0, $.length - V)),
                          (w[m + 1][1] = D.substring(V)),
                          m++)
                        : (Z >= $.length / 2 || Z >= D.length / 2) &&
                          (w.splice(m, 0, [r, $.substring(0, Z)]),
                          (w[m - 1][0] = e),
                          (w[m - 1][1] = D.substring(0, D.length - Z)),
                          (w[m + 1][0] = t),
                          (w[m + 1][1] = $.substring(Z)),
                          m++),
                        m++);
                }
                m++;
            }
        }
        var h = /[^a-zA-Z0-9]/,
            g = /\s/,
            v = /[\r\n]/,
            b = /\n\r?\n$/,
            _ = /^\r?\n\r?\n/;
        function y(w) {
            function R(Z, U) {
                if (!Z || !U) return 6;
                var W = Z.charAt(Z.length - 1),
                    Y = U.charAt(0),
                    X = W.match(h),
                    ne = Y.match(h),
                    se = X && W.match(g),
                    ie = ne && Y.match(g),
                    ee = se && W.match(v),
                    le = ie && Y.match(v),
                    me = ee && Z.match(b),
                    ue = le && U.match(_);
                return me || ue
                    ? 5
                    : ee || le
                      ? 4
                      : X && !se && ie
                        ? 3
                        : se || ie
                          ? 2
                          : X || ne
                            ? 1
                            : 0;
            }
            for (var T = 1; T < w.length - 1; ) {
                if (w[T - 1][0] == r && w[T + 1][0] == r) {
                    var k = w[T - 1][1],
                        M = w[T][1],
                        m = w[T + 1][1],
                        A = f(k, M);
                    if (A) {
                        var O = M.substring(M.length - A);
                        ((k = k.substring(0, k.length - A)),
                            (M = O + M.substring(0, M.length - A)),
                            (m = O + m));
                    }
                    for (
                        var B = k, I = M, $ = m, D = R(k, M) + R(M, m);
                        M.charAt(0) === m.charAt(0);
                    ) {
                        ((k += M.charAt(0)),
                            (M = M.substring(1) + m.charAt(0)),
                            (m = m.substring(1)));
                        var V = R(k, M) + R(M, m);
                        V >= D && ((D = V), (B = k), (I = M), ($ = m));
                    }
                    w[T - 1][1] != B &&
                        (B ? (w[T - 1][1] = B) : (w.splice(T - 1, 1), T--),
                        (w[T][1] = I),
                        $ ? (w[T + 1][1] = $) : (w.splice(T + 1, 1), T--));
                }
                T++;
            }
        }
        function S(w, R) {
            w.push([r, ""]);
            for (var T = 0, k = 0, M = 0, m = "", A = "", O; T < w.length; ) {
                if (T < w.length - 1 && !w[T][1]) {
                    w.splice(T, 1);
                    continue;
                }
                switch (w[T][0]) {
                    case e:
                        (M++, (A += w[T][1]), T++);
                        break;
                    case t:
                        (k++, (m += w[T][1]), T++);
                        break;
                    case r:
                        var B = T - M - k - 1;
                        if (R) {
                            if (B >= 0 && F(w[B][1])) {
                                var I = w[B][1].slice(-1);
                                if (
                                    ((w[B][1] = w[B][1].slice(0, -1)),
                                    (m = I + m),
                                    (A = I + A),
                                    !w[B][1])
                                ) {
                                    (w.splice(B, 1), T--);
                                    var $ = B - 1;
                                    (w[$] &&
                                        w[$][0] === e &&
                                        (M++, (A = w[$][1] + A), $--),
                                        w[$] &&
                                            w[$][0] === t &&
                                            (k++, (m = w[$][1] + m), $--),
                                        (B = $));
                                }
                            }
                            if (q(w[T][1])) {
                                var I = w[T][1].charAt(0);
                                ((w[T][1] = w[T][1].slice(1)),
                                    (m += I),
                                    (A += I));
                            }
                        }
                        if (T < w.length - 1 && !w[T][1]) {
                            w.splice(T, 1);
                            break;
                        }
                        if (m.length > 0 || A.length > 0) {
                            m.length > 0 &&
                                A.length > 0 &&
                                ((O = a(A, m)),
                                O !== 0 &&
                                    (B >= 0
                                        ? (w[B][1] += A.substring(0, O))
                                        : (w.splice(0, 0, [
                                              r,
                                              A.substring(0, O),
                                          ]),
                                          T++),
                                    (A = A.substring(O)),
                                    (m = m.substring(O))),
                                (O = f(A, m)),
                                O !== 0 &&
                                    ((w[T][1] =
                                        A.substring(A.length - O) + w[T][1]),
                                    (A = A.substring(0, A.length - O)),
                                    (m = m.substring(0, m.length - O))));
                            var D = M + k;
                            m.length === 0 && A.length === 0
                                ? (w.splice(T - D, D), (T = T - D))
                                : m.length === 0
                                  ? (w.splice(T - D, D, [e, A]),
                                    (T = T - D + 1))
                                  : A.length === 0
                                    ? (w.splice(T - D, D, [t, m]),
                                      (T = T - D + 1))
                                    : (w.splice(T - D, D, [t, m], [e, A]),
                                      (T = T - D + 2));
                        }
                        (T !== 0 && w[T - 1][0] === r
                            ? ((w[T - 1][1] += w[T][1]), w.splice(T, 1))
                            : T++,
                            (M = 0),
                            (k = 0),
                            (m = ""),
                            (A = ""));
                        break;
                }
            }
            w[w.length - 1][1] === "" && w.pop();
            var V = !1;
            for (T = 1; T < w.length - 1; )
                (w[T - 1][0] === r &&
                    w[T + 1][0] === r &&
                    (w[T][1].substring(w[T][1].length - w[T - 1][1].length) ===
                    w[T - 1][1]
                        ? ((w[T][1] =
                              w[T - 1][1] +
                              w[T][1].substring(
                                  0,
                                  w[T][1].length - w[T - 1][1].length,
                              )),
                          (w[T + 1][1] = w[T - 1][1] + w[T + 1][1]),
                          w.splice(T - 1, 1),
                          (V = !0))
                        : w[T][1].substring(0, w[T + 1][1].length) ==
                              w[T + 1][1] &&
                          ((w[T - 1][1] += w[T + 1][1]),
                          (w[T][1] =
                              w[T][1].substring(w[T + 1][1].length) +
                              w[T + 1][1]),
                          w.splice(T + 1, 1),
                          (V = !0))),
                    T++);
            V && S(w, R);
        }
        function N(w) {
            return w >= 55296 && w <= 56319;
        }
        function P(w) {
            return w >= 56320 && w <= 57343;
        }
        function q(w) {
            return P(w.charCodeAt(0));
        }
        function F(w) {
            return N(w.charCodeAt(w.length - 1));
        }
        function te(w) {
            for (var R = [], T = 0; T < w.length; T++)
                w[T][1].length > 0 && R.push(w[T]);
            return R;
        }
        function re(w, R, T, k) {
            return F(w) || q(k)
                ? null
                : te([
                      [r, w],
                      [t, R],
                      [e, T],
                      [r, k],
                  ]);
        }
        function de(w, R, T) {
            var k = typeof T == "number" ? { index: T, length: 0 } : T.oldRange,
                M = typeof T == "number" ? null : T.newRange,
                m = w.length,
                A = R.length;
            if (k.length === 0 && (M === null || M.length === 0)) {
                var O = k.index,
                    B = w.slice(0, O),
                    I = w.slice(O),
                    $ = M ? M.index : null;
                e: {
                    var D = O + A - m;
                    if (($ !== null && $ !== D) || D < 0 || D > A) break e;
                    var V = R.slice(0, D),
                        Z = R.slice(D);
                    if (Z !== I) break e;
                    var U = Math.min(O, D),
                        W = B.slice(0, U),
                        Y = V.slice(0, U);
                    if (W !== Y) break e;
                    var X = B.slice(U),
                        ne = V.slice(U);
                    return re(W, X, ne, I);
                }
                e: {
                    if ($ !== null && $ !== O) break e;
                    var se = O,
                        V = R.slice(0, se),
                        Z = R.slice(se);
                    if (V !== B) break e;
                    var ie = Math.min(m - se, A - se),
                        ee = I.slice(I.length - ie),
                        le = Z.slice(Z.length - ie);
                    if (ee !== le) break e;
                    var X = I.slice(0, I.length - ie),
                        ne = Z.slice(0, Z.length - ie);
                    return re(B, X, ne, ee);
                }
            }
            if (k.length > 0 && M && M.length === 0)
                e: {
                    var W = w.slice(0, k.index),
                        ee = w.slice(k.index + k.length),
                        U = W.length,
                        ie = ee.length;
                    if (A < U + ie) break e;
                    var Y = R.slice(0, U),
                        le = R.slice(A - ie);
                    if (W !== Y || ee !== le) break e;
                    var X = w.slice(U, m - ie),
                        ne = R.slice(U, A - ie);
                    return re(W, X, ne, ee);
                }
            return null;
        }
        function ce(w, R, T, k) {
            return n(w, R, T, k, !0);
        }
        return (
            (ce.INSERT = e),
            (ce.DELETE = t),
            (ce.EQUAL = r),
            (Eo = ce),
            Eo
        );
    }
    var qn = { exports: {} };
    qn.exports;
    var v0;
    function du() {
        return (
            v0 ||
                ((v0 = 1),
                (function (t, e) {
                    var r = 200,
                        n = "__lodash_hash_undefined__",
                        s = 9007199254740991,
                        i = "[object Arguments]",
                        o = "[object Array]",
                        a = "[object Boolean]",
                        l = "[object Date]",
                        f = "[object Error]",
                        u = "[object Function]",
                        d = "[object GeneratorFunction]",
                        h = "[object Map]",
                        g = "[object Number]",
                        v = "[object Object]",
                        b = "[object Promise]",
                        _ = "[object RegExp]",
                        y = "[object Set]",
                        S = "[object String]",
                        N = "[object Symbol]",
                        P = "[object WeakMap]",
                        q = "[object ArrayBuffer]",
                        F = "[object DataView]",
                        te = "[object Float32Array]",
                        re = "[object Float64Array]",
                        de = "[object Int8Array]",
                        ce = "[object Int16Array]",
                        w = "[object Int32Array]",
                        R = "[object Uint8Array]",
                        T = "[object Uint8ClampedArray]",
                        k = "[object Uint16Array]",
                        M = "[object Uint32Array]",
                        m = /[\\^$.*+?()[\]{}|]/g,
                        A = /\w*$/,
                        O = /^\[object .+?Constructor\]$/,
                        B = /^(?:0|[1-9]\d*)$/,
                        I = {};
                    ((I[i] =
                        I[o] =
                        I[q] =
                        I[F] =
                        I[a] =
                        I[l] =
                        I[te] =
                        I[re] =
                        I[de] =
                        I[ce] =
                        I[w] =
                        I[h] =
                        I[g] =
                        I[v] =
                        I[_] =
                        I[y] =
                        I[S] =
                        I[N] =
                        I[R] =
                        I[T] =
                        I[k] =
                        I[M] =
                            !0),
                        (I[f] = I[u] = I[P] = !1));
                    var $ =
                            typeof $t == "object" &&
                            $t &&
                            $t.Object === Object &&
                            $t,
                        D =
                            typeof self == "object" &&
                            self &&
                            self.Object === Object &&
                            self,
                        V = $ || D || Function("return this")(),
                        Z = e && !e.nodeType && e,
                        U = Z && !0 && t && !t.nodeType && t,
                        W = U && U.exports === Z;
                    function Y(c, p) {
                        return (c.set(p[0], p[1]), c);
                    }
                    function X(c, p) {
                        return (c.add(p), c);
                    }
                    function ne(c, p) {
                        for (
                            var E = -1, C = c ? c.length : 0;
                            ++E < C && p(c[E], E, c) !== !1;
                        );
                        return c;
                    }
                    function se(c, p) {
                        for (var E = -1, C = p.length, Q = c.length; ++E < C; )
                            c[Q + E] = p[E];
                        return c;
                    }
                    function ie(c, p, E, C) {
                        for (var Q = -1, G = c ? c.length : 0; ++Q < G; )
                            E = p(E, c[Q], Q, c);
                        return E;
                    }
                    function ee(c, p) {
                        for (var E = -1, C = Array(c); ++E < c; ) C[E] = p(E);
                        return C;
                    }
                    function le(c, p) {
                        return c?.[p];
                    }
                    function me(c) {
                        var p = !1;
                        if (c != null && typeof c.toString != "function")
                            try {
                                p = !!(c + "");
                            } catch {}
                        return p;
                    }
                    function ue(c) {
                        var p = -1,
                            E = Array(c.size);
                        return (
                            c.forEach(function (C, Q) {
                                E[++p] = [Q, C];
                            }),
                            E
                        );
                    }
                    function J(c, p) {
                        return function (E) {
                            return c(p(E));
                        };
                    }
                    function xe(c) {
                        var p = -1,
                            E = Array(c.size);
                        return (
                            c.forEach(function (C) {
                                E[++p] = C;
                            }),
                            E
                        );
                    }
                    var ze = Array.prototype,
                        Xe = Function.prototype,
                        Et = Object.prototype,
                        Gt = V["__core-js_shared__"],
                        ct = (function () {
                            var c = /[^.]+$/.exec(
                                (Gt && Gt.keys && Gt.keys.IE_PROTO) || "",
                            );
                            return c ? "Symbol(src)_1." + c : "";
                        })(),
                        is = Xe.toString,
                        ut = Et.hasOwnProperty,
                        Nr = Et.toString,
                        _i = RegExp(
                            "^" +
                                is
                                    .call(ut)
                                    .replace(m, "\\$&")
                                    .replace(
                                        /hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,
                                        "$1.*?",
                                    ) +
                                "$",
                        ),
                        Zt = W ? V.Buffer : void 0,
                        Or = V.Symbol,
                        fn = V.Uint8Array,
                        Ke = J(Object.getPrototypeOf, Object),
                        os = Object.create,
                        as = Et.propertyIsEnumerable,
                        xi = ze.splice,
                        cn = Object.getOwnPropertySymbols,
                        Cr = Zt ? Zt.isBuffer : void 0,
                        ls = J(Object.keys, Object),
                        Lr = Ye(V, "DataView"),
                        Xt = Ye(V, "Map"),
                        Je = Ye(V, "Promise"),
                        Rr = Ye(V, "Set"),
                        un = Ye(V, "WeakMap"),
                        Jt = Ye(Object, "create"),
                        hn = Ie(Lr),
                        Yt = Ie(Xt),
                        dn = Ie(Je),
                        pn = Ie(Rr),
                        gn = Ie(un),
                        kt = Or ? Or.prototype : void 0,
                        fs = kt ? kt.valueOf : void 0;
                    function _t(c) {
                        var p = -1,
                            E = c ? c.length : 0;
                        for (this.clear(); ++p < E; ) {
                            var C = c[p];
                            this.set(C[0], C[1]);
                        }
                    }
                    function Ai() {
                        this.__data__ = Jt ? Jt(null) : {};
                    }
                    function Si(c) {
                        return this.has(c) && delete this.__data__[c];
                    }
                    function Ti(c) {
                        var p = this.__data__;
                        if (Jt) {
                            var E = p[c];
                            return E === n ? void 0 : E;
                        }
                        return ut.call(p, c) ? p[c] : void 0;
                    }
                    function cs(c) {
                        var p = this.__data__;
                        return Jt ? p[c] !== void 0 : ut.call(p, c);
                    }
                    function mn(c, p) {
                        var E = this.__data__;
                        return ((E[c] = Jt && p === void 0 ? n : p), this);
                    }
                    ((_t.prototype.clear = Ai),
                        (_t.prototype.delete = Si),
                        (_t.prototype.get = Ti),
                        (_t.prototype.has = cs),
                        (_t.prototype.set = mn));
                    function Ae(c) {
                        var p = -1,
                            E = c ? c.length : 0;
                        for (this.clear(); ++p < E; ) {
                            var C = c[p];
                            this.set(C[0], C[1]);
                        }
                    }
                    function Ni() {
                        this.__data__ = [];
                    }
                    function Oi(c) {
                        var p = this.__data__,
                            E = kr(p, c);
                        if (E < 0) return !1;
                        var C = p.length - 1;
                        return (E == C ? p.pop() : xi.call(p, E, 1), !0);
                    }
                    function Ci(c) {
                        var p = this.__data__,
                            E = kr(p, c);
                        return E < 0 ? void 0 : p[E][1];
                    }
                    function Li(c) {
                        return kr(this.__data__, c) > -1;
                    }
                    function Ri(c, p) {
                        var E = this.__data__,
                            C = kr(E, c);
                        return (C < 0 ? E.push([c, p]) : (E[C][1] = p), this);
                    }
                    ((Ae.prototype.clear = Ni),
                        (Ae.prototype.delete = Oi),
                        (Ae.prototype.get = Ci),
                        (Ae.prototype.has = Li),
                        (Ae.prototype.set = Ri));
                    function Oe(c) {
                        var p = -1,
                            E = c ? c.length : 0;
                        for (this.clear(); ++p < E; ) {
                            var C = c[p];
                            this.set(C[0], C[1]);
                        }
                    }
                    function qi() {
                        this.__data__ = {
                            hash: new _t(),
                            map: new (Xt || Ae)(),
                            string: new _t(),
                        };
                    }
                    function ki(c) {
                        return er(this, c).delete(c);
                    }
                    function Ii(c) {
                        return er(this, c).get(c);
                    }
                    function Pi(c) {
                        return er(this, c).has(c);
                    }
                    function Mi(c, p) {
                        return (er(this, c).set(c, p), this);
                    }
                    ((Oe.prototype.clear = qi),
                        (Oe.prototype.delete = ki),
                        (Oe.prototype.get = Ii),
                        (Oe.prototype.has = Pi),
                        (Oe.prototype.set = Mi));
                    function $e(c) {
                        this.__data__ = new Ae(c);
                    }
                    function Bi() {
                        this.__data__ = new Ae();
                    }
                    function $i(c) {
                        return this.__data__.delete(c);
                    }
                    function Di(c) {
                        return this.__data__.get(c);
                    }
                    function ji(c) {
                        return this.__data__.has(c);
                    }
                    function Ui(c, p) {
                        var E = this.__data__;
                        if (E instanceof Ae) {
                            var C = E.__data__;
                            if (!Xt || C.length < r - 1)
                                return (C.push([c, p]), this);
                            E = this.__data__ = new Oe(C);
                        }
                        return (E.set(c, p), this);
                    }
                    (($e.prototype.clear = Bi),
                        ($e.prototype.delete = $i),
                        ($e.prototype.get = Di),
                        ($e.prototype.has = ji),
                        ($e.prototype.set = Ui));
                    function qr(c, p) {
                        var E = wn(c) || Pr(c) ? ee(c.length, String) : [],
                            C = E.length,
                            Q = !!C;
                        for (var G in c)
                            ut.call(c, G) &&
                                !(Q && (G == "length" || to(G, C))) &&
                                E.push(G);
                        return E;
                    }
                    function us(c, p, E) {
                        var C = c[p];
                        (!(ut.call(c, p) && ms(C, E)) ||
                            (E === void 0 && !(p in c))) &&
                            (c[p] = E);
                    }
                    function kr(c, p) {
                        for (var E = c.length; E--; )
                            if (ms(c[E][0], p)) return E;
                        return -1;
                    }
                    function ht(c, p) {
                        return c && vn(p, _n(p), c);
                    }
                    function bn(c, p, E, C, Q, G, ae) {
                        var oe;
                        if (
                            (C && (oe = G ? C(c, Q, G, ae) : C(c)),
                            oe !== void 0)
                        )
                            return oe;
                        if (!pt(c)) return c;
                        var ve = wn(c);
                        if (ve) {
                            if (((oe = Qi(c)), !p)) return Xi(c, oe);
                        } else {
                            var fe = At(c),
                                Ce = fe == u || fe == d;
                            if (bs(c)) return Ir(c, p);
                            if (fe == v || fe == i || (Ce && !G)) {
                                if (me(c)) return G ? c : {};
                                if (((oe = dt(Ce ? {} : c)), !p))
                                    return Ji(c, ht(oe, c));
                            } else {
                                if (!I[fe]) return G ? c : {};
                                oe = eo(c, fe, bn, p);
                            }
                        }
                        ae || (ae = new $e());
                        var De = ae.get(c);
                        if (De) return De;
                        if ((ae.set(c, oe), !ve)) var _e = E ? Yi(c) : _n(c);
                        return (
                            ne(_e || c, function (Le, Se) {
                                (_e && ((Se = Le), (Le = c[Se])),
                                    us(oe, Se, bn(Le, p, E, C, Se, c, ae)));
                            }),
                            oe
                        );
                    }
                    function Fi(c) {
                        return pt(c) ? os(c) : {};
                    }
                    function Hi(c, p, E) {
                        var C = p(c);
                        return wn(c) ? C : se(C, E(c));
                    }
                    function zi(c) {
                        return Nr.call(c);
                    }
                    function Ki(c) {
                        if (!pt(c) || no(c)) return !1;
                        var p = En(c) || me(c) ? _i : O;
                        return p.test(Ie(c));
                    }
                    function Wi(c) {
                        if (!ps(c)) return ls(c);
                        var p = [];
                        for (var E in Object(c))
                            ut.call(c, E) && E != "constructor" && p.push(E);
                        return p;
                    }
                    function Ir(c, p) {
                        if (p) return c.slice();
                        var E = new c.constructor(c.length);
                        return (c.copy(E), E);
                    }
                    function yn(c) {
                        var p = new c.constructor(c.byteLength);
                        return (new fn(p).set(new fn(c)), p);
                    }
                    function Qt(c, p) {
                        var E = p ? yn(c.buffer) : c.buffer;
                        return new c.constructor(E, c.byteOffset, c.byteLength);
                    }
                    function hs(c, p, E) {
                        var C = p ? E(ue(c), !0) : ue(c);
                        return ie(C, Y, new c.constructor());
                    }
                    function ds(c) {
                        var p = new c.constructor(c.source, A.exec(c));
                        return ((p.lastIndex = c.lastIndex), p);
                    }
                    function Vi(c, p, E) {
                        var C = p ? E(xe(c), !0) : xe(c);
                        return ie(C, X, new c.constructor());
                    }
                    function Gi(c) {
                        return fs ? Object(fs.call(c)) : {};
                    }
                    function Zi(c, p) {
                        var E = p ? yn(c.buffer) : c.buffer;
                        return new c.constructor(E, c.byteOffset, c.length);
                    }
                    function Xi(c, p) {
                        var E = -1,
                            C = c.length;
                        for (p || (p = Array(C)); ++E < C; ) p[E] = c[E];
                        return p;
                    }
                    function vn(c, p, E, C) {
                        E || (E = {});
                        for (var Q = -1, G = p.length; ++Q < G; ) {
                            var ae = p[Q],
                                oe = void 0;
                            us(E, ae, oe === void 0 ? c[ae] : oe);
                        }
                        return E;
                    }
                    function Ji(c, p) {
                        return vn(c, xt(c), p);
                    }
                    function Yi(c) {
                        return Hi(c, _n, xt);
                    }
                    function er(c, p) {
                        var E = c.__data__;
                        return ro(p)
                            ? E[typeof p == "string" ? "string" : "hash"]
                            : E.map;
                    }
                    function Ye(c, p) {
                        var E = le(c, p);
                        return Ki(E) ? E : void 0;
                    }
                    var xt = cn ? J(cn, Object) : io,
                        At = zi;
                    ((Lr && At(new Lr(new ArrayBuffer(1))) != F) ||
                        (Xt && At(new Xt()) != h) ||
                        (Je && At(Je.resolve()) != b) ||
                        (Rr && At(new Rr()) != y) ||
                        (un && At(new un()) != P)) &&
                        (At = function (c) {
                            var p = Nr.call(c),
                                E = p == v ? c.constructor : void 0,
                                C = E ? Ie(E) : void 0;
                            if (C)
                                switch (C) {
                                    case hn:
                                        return F;
                                    case Yt:
                                        return h;
                                    case dn:
                                        return b;
                                    case pn:
                                        return y;
                                    case gn:
                                        return P;
                                }
                            return p;
                        });
                    function Qi(c) {
                        var p = c.length,
                            E = c.constructor(p);
                        return (
                            p &&
                                typeof c[0] == "string" &&
                                ut.call(c, "index") &&
                                ((E.index = c.index), (E.input = c.input)),
                            E
                        );
                    }
                    function dt(c) {
                        return typeof c.constructor == "function" && !ps(c)
                            ? Fi(Ke(c))
                            : {};
                    }
                    function eo(c, p, E, C) {
                        var Q = c.constructor;
                        switch (p) {
                            case q:
                                return yn(c);
                            case a:
                            case l:
                                return new Q(+c);
                            case F:
                                return Qt(c, C);
                            case te:
                            case re:
                            case de:
                            case ce:
                            case w:
                            case R:
                            case T:
                            case k:
                            case M:
                                return Zi(c, C);
                            case h:
                                return hs(c, C, E);
                            case g:
                            case S:
                                return new Q(c);
                            case _:
                                return ds(c);
                            case y:
                                return Vi(c, C, E);
                            case N:
                                return Gi(c);
                        }
                    }
                    function to(c, p) {
                        return (
                            (p = p ?? s),
                            !!p &&
                                (typeof c == "number" || B.test(c)) &&
                                c > -1 &&
                                c % 1 == 0 &&
                                c < p
                        );
                    }
                    function ro(c) {
                        var p = typeof c;
                        return p == "string" ||
                            p == "number" ||
                            p == "symbol" ||
                            p == "boolean"
                            ? c !== "__proto__"
                            : c === null;
                    }
                    function no(c) {
                        return !!ct && ct in c;
                    }
                    function ps(c) {
                        var p = c && c.constructor,
                            E = (typeof p == "function" && p.prototype) || Et;
                        return c === E;
                    }
                    function Ie(c) {
                        if (c != null) {
                            try {
                                return is.call(c);
                            } catch {}
                            try {
                                return c + "";
                            } catch {}
                        }
                        return "";
                    }
                    function gs(c) {
                        return bn(c, !0, !0);
                    }
                    function ms(c, p) {
                        return c === p || (c !== c && p !== p);
                    }
                    function Pr(c) {
                        return (
                            so(c) &&
                            ut.call(c, "callee") &&
                            (!as.call(c, "callee") || Nr.call(c) == i)
                        );
                    }
                    var wn = Array.isArray;
                    function Mr(c) {
                        return c != null && ys(c.length) && !En(c);
                    }
                    function so(c) {
                        return vs(c) && Mr(c);
                    }
                    var bs = Cr || oo;
                    function En(c) {
                        var p = pt(c) ? Nr.call(c) : "";
                        return p == u || p == d;
                    }
                    function ys(c) {
                        return (
                            typeof c == "number" &&
                            c > -1 &&
                            c % 1 == 0 &&
                            c <= s
                        );
                    }
                    function pt(c) {
                        var p = typeof c;
                        return !!c && (p == "object" || p == "function");
                    }
                    function vs(c) {
                        return !!c && typeof c == "object";
                    }
                    function _n(c) {
                        return Mr(c) ? qr(c) : Wi(c);
                    }
                    function io() {
                        return [];
                    }
                    function oo() {
                        return !1;
                    }
                    t.exports = gs;
                })(qn, qn.exports)),
            qn.exports
        );
    }
    var kn = { exports: {} };
    kn.exports;
    var w0;
    function pu() {
        return (
            w0 ||
                ((w0 = 1),
                (function (t, e) {
                    var r = 200,
                        n = "__lodash_hash_undefined__",
                        s = 1,
                        i = 2,
                        o = 9007199254740991,
                        a = "[object Arguments]",
                        l = "[object Array]",
                        f = "[object AsyncFunction]",
                        u = "[object Boolean]",
                        d = "[object Date]",
                        h = "[object Error]",
                        g = "[object Function]",
                        v = "[object GeneratorFunction]",
                        b = "[object Map]",
                        _ = "[object Number]",
                        y = "[object Null]",
                        S = "[object Object]",
                        N = "[object Promise]",
                        P = "[object Proxy]",
                        q = "[object RegExp]",
                        F = "[object Set]",
                        te = "[object String]",
                        re = "[object Symbol]",
                        de = "[object Undefined]",
                        ce = "[object WeakMap]",
                        w = "[object ArrayBuffer]",
                        R = "[object DataView]",
                        T = "[object Float32Array]",
                        k = "[object Float64Array]",
                        M = "[object Int8Array]",
                        m = "[object Int16Array]",
                        A = "[object Int32Array]",
                        O = "[object Uint8Array]",
                        B = "[object Uint8ClampedArray]",
                        I = "[object Uint16Array]",
                        $ = "[object Uint32Array]",
                        D = /[\\^$.*+?()[\]{}|]/g,
                        V = /^\[object .+?Constructor\]$/,
                        Z = /^(?:0|[1-9]\d*)$/,
                        U = {};
                    ((U[T] =
                        U[k] =
                        U[M] =
                        U[m] =
                        U[A] =
                        U[O] =
                        U[B] =
                        U[I] =
                        U[$] =
                            !0),
                        (U[a] =
                            U[l] =
                            U[w] =
                            U[u] =
                            U[R] =
                            U[d] =
                            U[h] =
                            U[g] =
                            U[b] =
                            U[_] =
                            U[S] =
                            U[q] =
                            U[F] =
                            U[te] =
                            U[ce] =
                                !1));
                    var W =
                            typeof $t == "object" &&
                            $t &&
                            $t.Object === Object &&
                            $t,
                        Y =
                            typeof self == "object" &&
                            self &&
                            self.Object === Object &&
                            self,
                        X = W || Y || Function("return this")(),
                        ne = e && !e.nodeType && e,
                        se = ne && !0 && t && !t.nodeType && t,
                        ie = se && se.exports === ne,
                        ee = ie && W.process,
                        le = (function () {
                            try {
                                return ee && ee.binding && ee.binding("util");
                            } catch {}
                        })(),
                        me = le && le.isTypedArray;
                    function ue(c, p) {
                        for (
                            var E = -1,
                                C = c == null ? 0 : c.length,
                                Q = 0,
                                G = [];
                            ++E < C;
                        ) {
                            var ae = c[E];
                            p(ae, E, c) && (G[Q++] = ae);
                        }
                        return G;
                    }
                    function J(c, p) {
                        for (var E = -1, C = p.length, Q = c.length; ++E < C; )
                            c[Q + E] = p[E];
                        return c;
                    }
                    function xe(c, p) {
                        for (
                            var E = -1, C = c == null ? 0 : c.length;
                            ++E < C;
                        )
                            if (p(c[E], E, c)) return !0;
                        return !1;
                    }
                    function ze(c, p) {
                        for (var E = -1, C = Array(c); ++E < c; ) C[E] = p(E);
                        return C;
                    }
                    function Xe(c) {
                        return function (p) {
                            return c(p);
                        };
                    }
                    function Et(c, p) {
                        return c.has(p);
                    }
                    function Gt(c, p) {
                        return c?.[p];
                    }
                    function ct(c) {
                        var p = -1,
                            E = Array(c.size);
                        return (
                            c.forEach(function (C, Q) {
                                E[++p] = [Q, C];
                            }),
                            E
                        );
                    }
                    function is(c, p) {
                        return function (E) {
                            return c(p(E));
                        };
                    }
                    function ut(c) {
                        var p = -1,
                            E = Array(c.size);
                        return (
                            c.forEach(function (C) {
                                E[++p] = C;
                            }),
                            E
                        );
                    }
                    var Nr = Array.prototype,
                        _i = Function.prototype,
                        Zt = Object.prototype,
                        Or = X["__core-js_shared__"],
                        fn = _i.toString,
                        Ke = Zt.hasOwnProperty,
                        os = (function () {
                            var c = /[^.]+$/.exec(
                                (Or && Or.keys && Or.keys.IE_PROTO) || "",
                            );
                            return c ? "Symbol(src)_1." + c : "";
                        })(),
                        as = Zt.toString,
                        xi = RegExp(
                            "^" +
                                fn
                                    .call(Ke)
                                    .replace(D, "\\$&")
                                    .replace(
                                        /hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g,
                                        "$1.*?",
                                    ) +
                                "$",
                        ),
                        cn = ie ? X.Buffer : void 0,
                        Cr = X.Symbol,
                        ls = X.Uint8Array,
                        Lr = Zt.propertyIsEnumerable,
                        Xt = Nr.splice,
                        Je = Cr ? Cr.toStringTag : void 0,
                        Rr = Object.getOwnPropertySymbols,
                        un = cn ? cn.isBuffer : void 0,
                        Jt = is(Object.keys, Object),
                        hn = xt(X, "DataView"),
                        Yt = xt(X, "Map"),
                        dn = xt(X, "Promise"),
                        pn = xt(X, "Set"),
                        gn = xt(X, "WeakMap"),
                        kt = xt(Object, "create"),
                        fs = Ie(hn),
                        _t = Ie(Yt),
                        Ai = Ie(dn),
                        Si = Ie(pn),
                        Ti = Ie(gn),
                        cs = Cr ? Cr.prototype : void 0,
                        mn = cs ? cs.valueOf : void 0;
                    function Ae(c) {
                        var p = -1,
                            E = c == null ? 0 : c.length;
                        for (this.clear(); ++p < E; ) {
                            var C = c[p];
                            this.set(C[0], C[1]);
                        }
                    }
                    function Ni() {
                        ((this.__data__ = kt ? kt(null) : {}), (this.size = 0));
                    }
                    function Oi(c) {
                        var p = this.has(c) && delete this.__data__[c];
                        return ((this.size -= p ? 1 : 0), p);
                    }
                    function Ci(c) {
                        var p = this.__data__;
                        if (kt) {
                            var E = p[c];
                            return E === n ? void 0 : E;
                        }
                        return Ke.call(p, c) ? p[c] : void 0;
                    }
                    function Li(c) {
                        var p = this.__data__;
                        return kt ? p[c] !== void 0 : Ke.call(p, c);
                    }
                    function Ri(c, p) {
                        var E = this.__data__;
                        return (
                            (this.size += this.has(c) ? 0 : 1),
                            (E[c] = kt && p === void 0 ? n : p),
                            this
                        );
                    }
                    ((Ae.prototype.clear = Ni),
                        (Ae.prototype.delete = Oi),
                        (Ae.prototype.get = Ci),
                        (Ae.prototype.has = Li),
                        (Ae.prototype.set = Ri));
                    function Oe(c) {
                        var p = -1,
                            E = c == null ? 0 : c.length;
                        for (this.clear(); ++p < E; ) {
                            var C = c[p];
                            this.set(C[0], C[1]);
                        }
                    }
                    function qi() {
                        ((this.__data__ = []), (this.size = 0));
                    }
                    function ki(c) {
                        var p = this.__data__,
                            E = Ir(p, c);
                        if (E < 0) return !1;
                        var C = p.length - 1;
                        return (
                            E == C ? p.pop() : Xt.call(p, E, 1),
                            --this.size,
                            !0
                        );
                    }
                    function Ii(c) {
                        var p = this.__data__,
                            E = Ir(p, c);
                        return E < 0 ? void 0 : p[E][1];
                    }
                    function Pi(c) {
                        return Ir(this.__data__, c) > -1;
                    }
                    function Mi(c, p) {
                        var E = this.__data__,
                            C = Ir(E, c);
                        return (
                            C < 0
                                ? (++this.size, E.push([c, p]))
                                : (E[C][1] = p),
                            this
                        );
                    }
                    ((Oe.prototype.clear = qi),
                        (Oe.prototype.delete = ki),
                        (Oe.prototype.get = Ii),
                        (Oe.prototype.has = Pi),
                        (Oe.prototype.set = Mi));
                    function $e(c) {
                        var p = -1,
                            E = c == null ? 0 : c.length;
                        for (this.clear(); ++p < E; ) {
                            var C = c[p];
                            this.set(C[0], C[1]);
                        }
                    }
                    function Bi() {
                        ((this.size = 0),
                            (this.__data__ = {
                                hash: new Ae(),
                                map: new (Yt || Oe)(),
                                string: new Ae(),
                            }));
                    }
                    function $i(c) {
                        var p = Ye(this, c).delete(c);
                        return ((this.size -= p ? 1 : 0), p);
                    }
                    function Di(c) {
                        return Ye(this, c).get(c);
                    }
                    function ji(c) {
                        return Ye(this, c).has(c);
                    }
                    function Ui(c, p) {
                        var E = Ye(this, c),
                            C = E.size;
                        return (
                            E.set(c, p),
                            (this.size += E.size == C ? 0 : 1),
                            this
                        );
                    }
                    (($e.prototype.clear = Bi),
                        ($e.prototype.delete = $i),
                        ($e.prototype.get = Di),
                        ($e.prototype.has = ji),
                        ($e.prototype.set = Ui));
                    function qr(c) {
                        var p = -1,
                            E = c == null ? 0 : c.length;
                        for (this.__data__ = new $e(); ++p < E; )
                            this.add(c[p]);
                    }
                    function us(c) {
                        return (this.__data__.set(c, n), this);
                    }
                    function kr(c) {
                        return this.__data__.has(c);
                    }
                    ((qr.prototype.add = qr.prototype.push = us),
                        (qr.prototype.has = kr));
                    function ht(c) {
                        var p = (this.__data__ = new Oe(c));
                        this.size = p.size;
                    }
                    function bn() {
                        ((this.__data__ = new Oe()), (this.size = 0));
                    }
                    function Fi(c) {
                        var p = this.__data__,
                            E = p.delete(c);
                        return ((this.size = p.size), E);
                    }
                    function Hi(c) {
                        return this.__data__.get(c);
                    }
                    function zi(c) {
                        return this.__data__.has(c);
                    }
                    function Ki(c, p) {
                        var E = this.__data__;
                        if (E instanceof Oe) {
                            var C = E.__data__;
                            if (!Yt || C.length < r - 1)
                                return (
                                    C.push([c, p]),
                                    (this.size = ++E.size),
                                    this
                                );
                            E = this.__data__ = new $e(C);
                        }
                        return (E.set(c, p), (this.size = E.size), this);
                    }
                    ((ht.prototype.clear = bn),
                        (ht.prototype.delete = Fi),
                        (ht.prototype.get = Hi),
                        (ht.prototype.has = zi),
                        (ht.prototype.set = Ki));
                    function Wi(c, p) {
                        var E = Pr(c),
                            C = !E && ms(c),
                            Q = !E && !C && Mr(c),
                            G = !E && !C && !Q && vs(c),
                            ae = E || C || Q || G,
                            oe = ae ? ze(c.length, String) : [],
                            ve = oe.length;
                        for (var fe in c)
                            Ke.call(c, fe) &&
                                !(
                                    ae &&
                                    (fe == "length" ||
                                        (Q &&
                                            (fe == "offset" ||
                                                fe == "parent")) ||
                                        (G &&
                                            (fe == "buffer" ||
                                                fe == "byteLength" ||
                                                fe == "byteOffset")) ||
                                        eo(fe, ve))
                                ) &&
                                oe.push(fe);
                        return oe;
                    }
                    function Ir(c, p) {
                        for (var E = c.length; E--; )
                            if (gs(c[E][0], p)) return E;
                        return -1;
                    }
                    function yn(c, p, E) {
                        var C = p(c);
                        return Pr(c) ? C : J(C, E(c));
                    }
                    function Qt(c) {
                        return c == null
                            ? c === void 0
                                ? de
                                : y
                            : Je && Je in Object(c)
                              ? At(c)
                              : ps(c);
                    }
                    function hs(c) {
                        return pt(c) && Qt(c) == a;
                    }
                    function ds(c, p, E, C, Q) {
                        return c === p
                            ? !0
                            : c == null || p == null || (!pt(c) && !pt(p))
                              ? c !== c && p !== p
                              : Vi(c, p, E, C, ds, Q);
                    }
                    function Vi(c, p, E, C, Q, G) {
                        var ae = Pr(c),
                            oe = Pr(p),
                            ve = ae ? l : dt(c),
                            fe = oe ? l : dt(p);
                        ((ve = ve == a ? S : ve), (fe = fe == a ? S : fe));
                        var Ce = ve == S,
                            De = fe == S,
                            _e = ve == fe;
                        if (_e && Mr(c)) {
                            if (!Mr(p)) return !1;
                            ((ae = !0), (Ce = !1));
                        }
                        if (_e && !Ce)
                            return (
                                G || (G = new ht()),
                                ae || vs(c)
                                    ? vn(c, p, E, C, Q, G)
                                    : Ji(c, p, ve, E, C, Q, G)
                            );
                        if (!(E & s)) {
                            var Le = Ce && Ke.call(c, "__wrapped__"),
                                Se = De && Ke.call(p, "__wrapped__");
                            if (Le || Se) {
                                var It = Le ? c.value() : c,
                                    St = Se ? p.value() : p;
                                return (
                                    G || (G = new ht()),
                                    Q(It, St, E, C, G)
                                );
                            }
                        }
                        return _e
                            ? (G || (G = new ht()), Yi(c, p, E, C, Q, G))
                            : !1;
                    }
                    function Gi(c) {
                        if (!ys(c) || ro(c)) return !1;
                        var p = bs(c) ? xi : V;
                        return p.test(Ie(c));
                    }
                    function Zi(c) {
                        return pt(c) && En(c.length) && !!U[Qt(c)];
                    }
                    function Xi(c) {
                        if (!no(c)) return Jt(c);
                        var p = [];
                        for (var E in Object(c))
                            Ke.call(c, E) && E != "constructor" && p.push(E);
                        return p;
                    }
                    function vn(c, p, E, C, Q, G) {
                        var ae = E & s,
                            oe = c.length,
                            ve = p.length;
                        if (oe != ve && !(ae && ve > oe)) return !1;
                        var fe = G.get(c);
                        if (fe && G.get(p)) return fe == p;
                        var Ce = -1,
                            De = !0,
                            _e = E & i ? new qr() : void 0;
                        for (G.set(c, p), G.set(p, c); ++Ce < oe; ) {
                            var Le = c[Ce],
                                Se = p[Ce];
                            if (C)
                                var It = ae
                                    ? C(Se, Le, Ce, p, c, G)
                                    : C(Le, Se, Ce, c, p, G);
                            if (It !== void 0) {
                                if (It) continue;
                                De = !1;
                                break;
                            }
                            if (_e) {
                                if (
                                    !xe(p, function (St, tr) {
                                        if (
                                            !Et(_e, tr) &&
                                            (Le === St || Q(Le, St, E, C, G))
                                        )
                                            return _e.push(tr);
                                    })
                                ) {
                                    De = !1;
                                    break;
                                }
                            } else if (!(Le === Se || Q(Le, Se, E, C, G))) {
                                De = !1;
                                break;
                            }
                        }
                        return (G.delete(c), G.delete(p), De);
                    }
                    function Ji(c, p, E, C, Q, G, ae) {
                        switch (E) {
                            case R:
                                if (
                                    c.byteLength != p.byteLength ||
                                    c.byteOffset != p.byteOffset
                                )
                                    return !1;
                                ((c = c.buffer), (p = p.buffer));
                            case w:
                                return !(
                                    c.byteLength != p.byteLength ||
                                    !G(new ls(c), new ls(p))
                                );
                            case u:
                            case d:
                            case _:
                                return gs(+c, +p);
                            case h:
                                return (
                                    c.name == p.name && c.message == p.message
                                );
                            case q:
                            case te:
                                return c == p + "";
                            case b:
                                var oe = ct;
                            case F:
                                var ve = C & s;
                                if ((oe || (oe = ut), c.size != p.size && !ve))
                                    return !1;
                                var fe = ae.get(c);
                                if (fe) return fe == p;
                                ((C |= i), ae.set(c, p));
                                var Ce = vn(oe(c), oe(p), C, Q, G, ae);
                                return (ae.delete(c), Ce);
                            case re:
                                if (mn) return mn.call(c) == mn.call(p);
                        }
                        return !1;
                    }
                    function Yi(c, p, E, C, Q, G) {
                        var ae = E & s,
                            oe = er(c),
                            ve = oe.length,
                            fe = er(p),
                            Ce = fe.length;
                        if (ve != Ce && !ae) return !1;
                        for (var De = ve; De--; ) {
                            var _e = oe[De];
                            if (!(ae ? _e in p : Ke.call(p, _e))) return !1;
                        }
                        var Le = G.get(c);
                        if (Le && G.get(p)) return Le == p;
                        var Se = !0;
                        (G.set(c, p), G.set(p, c));
                        for (var It = ae; ++De < ve; ) {
                            _e = oe[De];
                            var St = c[_e],
                                tr = p[_e];
                            if (C)
                                var ul = ae
                                    ? C(tr, St, _e, p, c, G)
                                    : C(St, tr, _e, c, p, G);
                            if (
                                !(ul === void 0
                                    ? St === tr || Q(St, tr, E, C, G)
                                    : ul)
                            ) {
                                Se = !1;
                                break;
                            }
                            It || (It = _e == "constructor");
                        }
                        if (Se && !It) {
                            var ws = c.constructor,
                                Es = p.constructor;
                            ws != Es &&
                                "constructor" in c &&
                                "constructor" in p &&
                                !(
                                    typeof ws == "function" &&
                                    ws instanceof ws &&
                                    typeof Es == "function" &&
                                    Es instanceof Es
                                ) &&
                                (Se = !1);
                        }
                        return (G.delete(c), G.delete(p), Se);
                    }
                    function er(c) {
                        return yn(c, _n, Qi);
                    }
                    function Ye(c, p) {
                        var E = c.__data__;
                        return to(p)
                            ? E[typeof p == "string" ? "string" : "hash"]
                            : E.map;
                    }
                    function xt(c, p) {
                        var E = Gt(c, p);
                        return Gi(E) ? E : void 0;
                    }
                    function At(c) {
                        var p = Ke.call(c, Je),
                            E = c[Je];
                        try {
                            c[Je] = void 0;
                            var C = !0;
                        } catch {}
                        var Q = as.call(c);
                        return (C && (p ? (c[Je] = E) : delete c[Je]), Q);
                    }
                    var Qi = Rr
                            ? function (c) {
                                  return c == null
                                      ? []
                                      : ((c = Object(c)),
                                        ue(Rr(c), function (p) {
                                            return Lr.call(c, p);
                                        }));
                              }
                            : io,
                        dt = Qt;
                    ((hn && dt(new hn(new ArrayBuffer(1))) != R) ||
                        (Yt && dt(new Yt()) != b) ||
                        (dn && dt(dn.resolve()) != N) ||
                        (pn && dt(new pn()) != F) ||
                        (gn && dt(new gn()) != ce)) &&
                        (dt = function (c) {
                            var p = Qt(c),
                                E = p == S ? c.constructor : void 0,
                                C = E ? Ie(E) : "";
                            if (C)
                                switch (C) {
                                    case fs:
                                        return R;
                                    case _t:
                                        return b;
                                    case Ai:
                                        return N;
                                    case Si:
                                        return F;
                                    case Ti:
                                        return ce;
                                }
                            return p;
                        });
                    function eo(c, p) {
                        return (
                            (p = p ?? o),
                            !!p &&
                                (typeof c == "number" || Z.test(c)) &&
                                c > -1 &&
                                c % 1 == 0 &&
                                c < p
                        );
                    }
                    function to(c) {
                        var p = typeof c;
                        return p == "string" ||
                            p == "number" ||
                            p == "symbol" ||
                            p == "boolean"
                            ? c !== "__proto__"
                            : c === null;
                    }
                    function ro(c) {
                        return !!os && os in c;
                    }
                    function no(c) {
                        var p = c && c.constructor,
                            E = (typeof p == "function" && p.prototype) || Zt;
                        return c === E;
                    }
                    function ps(c) {
                        return as.call(c);
                    }
                    function Ie(c) {
                        if (c != null) {
                            try {
                                return fn.call(c);
                            } catch {}
                            try {
                                return c + "";
                            } catch {}
                        }
                        return "";
                    }
                    function gs(c, p) {
                        return c === p || (c !== c && p !== p);
                    }
                    var ms = hs(
                            (function () {
                                return arguments;
                            })(),
                        )
                            ? hs
                            : function (c) {
                                  return (
                                      pt(c) &&
                                      Ke.call(c, "callee") &&
                                      !Lr.call(c, "callee")
                                  );
                              },
                        Pr = Array.isArray;
                    function wn(c) {
                        return c != null && En(c.length) && !bs(c);
                    }
                    var Mr = un || oo;
                    function so(c, p) {
                        return ds(c, p);
                    }
                    function bs(c) {
                        if (!ys(c)) return !1;
                        var p = Qt(c);
                        return p == g || p == v || p == f || p == P;
                    }
                    function En(c) {
                        return (
                            typeof c == "number" &&
                            c > -1 &&
                            c % 1 == 0 &&
                            c <= o
                        );
                    }
                    function ys(c) {
                        var p = typeof c;
                        return c != null && (p == "object" || p == "function");
                    }
                    function pt(c) {
                        return c != null && typeof c == "object";
                    }
                    var vs = me ? Xe(me) : Zi;
                    function _n(c) {
                        return wn(c) ? Wi(c) : Xi(c);
                    }
                    function io() {
                        return [];
                    }
                    function oo() {
                        return !1;
                    }
                    t.exports = so;
                })(kn, kn.exports)),
            kn.exports
        );
    }
    var Rs = {},
        E0;
    function iw() {
        if (E0) return Rs;
        ((E0 = 1), Object.defineProperty(Rs, "__esModule", { value: !0 }));
        const t = du(),
            e = pu();
        var r;
        return (
            (function (n) {
                function s(l = {}, f = {}, u = !1) {
                    (typeof l != "object" && (l = {}),
                        typeof f != "object" && (f = {}));
                    let d = t(f);
                    u ||
                        (d = Object.keys(d).reduce(
                            (h, g) => (d[g] != null && (h[g] = d[g]), h),
                            {},
                        ));
                    for (const h in l)
                        l[h] !== void 0 && f[h] === void 0 && (d[h] = l[h]);
                    return Object.keys(d).length > 0 ? d : void 0;
                }
                n.compose = s;
                function i(l = {}, f = {}) {
                    (typeof l != "object" && (l = {}),
                        typeof f != "object" && (f = {}));
                    const u = Object.keys(l)
                        .concat(Object.keys(f))
                        .reduce(
                            (d, h) => (
                                e(l[h], f[h]) ||
                                    (d[h] = f[h] === void 0 ? null : f[h]),
                                d
                            ),
                            {},
                        );
                    return Object.keys(u).length > 0 ? u : void 0;
                }
                n.diff = i;
                function o(l = {}, f = {}) {
                    l = l || {};
                    const u = Object.keys(f).reduce(
                        (d, h) => (
                            f[h] !== l[h] && l[h] !== void 0 && (d[h] = f[h]),
                            d
                        ),
                        {},
                    );
                    return Object.keys(l).reduce(
                        (d, h) => (
                            l[h] !== f[h] && f[h] === void 0 && (d[h] = null),
                            d
                        ),
                        u,
                    );
                }
                n.invert = o;
                function a(l, f, u = !1) {
                    if (typeof l != "object") return f;
                    if (typeof f != "object") return;
                    if (!u) return f;
                    const d = Object.keys(f).reduce(
                        (h, g) => (l[g] === void 0 && (h[g] = f[g]), h),
                        {},
                    );
                    return Object.keys(d).length > 0 ? d : void 0;
                }
                n.transform = a;
            })(r || (r = {})),
            (Rs.default = r),
            Rs
        );
    }
    var qs = {},
        _0;
    function gu() {
        if (_0) return qs;
        ((_0 = 1), Object.defineProperty(qs, "__esModule", { value: !0 }));
        var t;
        return (
            (function (e) {
                function r(n) {
                    return typeof n.delete == "number"
                        ? n.delete
                        : typeof n.retain == "number"
                          ? n.retain
                          : typeof n.retain == "object" && n.retain !== null
                            ? 1
                            : typeof n.insert == "string"
                              ? n.insert.length
                              : 1;
                }
                e.length = r;
            })(t || (t = {})),
            (qs.default = t),
            qs
        );
    }
    var ks = {},
        x0;
    function ow() {
        if (x0) return ks;
        ((x0 = 1), Object.defineProperty(ks, "__esModule", { value: !0 }));
        const t = gu();
        class e {
            constructor(n) {
                ((this.ops = n), (this.index = 0), (this.offset = 0));
            }
            hasNext() {
                return this.peekLength() < 1 / 0;
            }
            next(n) {
                n || (n = 1 / 0);
                const s = this.ops[this.index];
                if (s) {
                    const i = this.offset,
                        o = t.default.length(s);
                    if (
                        (n >= o - i
                            ? ((n = o - i),
                              (this.index += 1),
                              (this.offset = 0))
                            : (this.offset += n),
                        typeof s.delete == "number")
                    )
                        return { delete: n };
                    {
                        const a = {};
                        return (
                            s.attributes && (a.attributes = s.attributes),
                            typeof s.retain == "number"
                                ? (a.retain = n)
                                : typeof s.retain == "object" &&
                                    s.retain !== null
                                  ? (a.retain = s.retain)
                                  : typeof s.insert == "string"
                                    ? (a.insert = s.insert.substr(i, n))
                                    : (a.insert = s.insert),
                            a
                        );
                    }
                } else return { retain: 1 / 0 };
            }
            peek() {
                return this.ops[this.index];
            }
            peekLength() {
                return this.ops[this.index]
                    ? t.default.length(this.ops[this.index]) - this.offset
                    : 1 / 0;
            }
            peekType() {
                const n = this.ops[this.index];
                return n
                    ? typeof n.delete == "number"
                        ? "delete"
                        : typeof n.retain == "number" ||
                            (typeof n.retain == "object" && n.retain !== null)
                          ? "retain"
                          : "insert"
                    : "retain";
            }
            rest() {
                if (this.hasNext()) {
                    if (this.offset === 0) return this.ops.slice(this.index);
                    {
                        const n = this.offset,
                            s = this.index,
                            i = this.next(),
                            o = this.ops.slice(this.index);
                        return (
                            (this.offset = n),
                            (this.index = s),
                            [i].concat(o)
                        );
                    }
                } else return [];
            }
        }
        return ((ks.default = e), ks);
    }
    var A0;
    function aw() {
        return (
            A0 ||
                ((A0 = 1),
                (function (t, e) {
                    (Object.defineProperty(e, "__esModule", { value: !0 }),
                        (e.AttributeMap = e.OpIterator = e.Op = void 0));
                    const r = sw(),
                        n = du(),
                        s = pu(),
                        i = iw();
                    e.AttributeMap = i.default;
                    const o = gu();
                    e.Op = o.default;
                    const a = ow();
                    e.OpIterator = a.default;
                    const l = "\0",
                        f = (d, h) => {
                            if (typeof d != "object" || d === null)
                                throw new Error(`cannot retain a ${typeof d}`);
                            if (typeof h != "object" || h === null)
                                throw new Error(`cannot retain a ${typeof h}`);
                            const g = Object.keys(d)[0];
                            if (!g || g !== Object.keys(h)[0])
                                throw new Error(
                                    `embed types not matched: ${g} != ${Object.keys(h)[0]}`,
                                );
                            return [g, d[g], h[g]];
                        };
                    class u {
                        constructor(h) {
                            Array.isArray(h)
                                ? (this.ops = h)
                                : h != null && Array.isArray(h.ops)
                                  ? (this.ops = h.ops)
                                  : (this.ops = []);
                        }
                        static registerEmbed(h, g) {
                            this.handlers[h] = g;
                        }
                        static unregisterEmbed(h) {
                            delete this.handlers[h];
                        }
                        static getHandler(h) {
                            const g = this.handlers[h];
                            if (!g)
                                throw new Error(
                                    `no handlers for embed type "${h}"`,
                                );
                            return g;
                        }
                        insert(h, g) {
                            const v = {};
                            return typeof h == "string" && h.length === 0
                                ? this
                                : ((v.insert = h),
                                  g != null &&
                                      typeof g == "object" &&
                                      Object.keys(g).length > 0 &&
                                      (v.attributes = g),
                                  this.push(v));
                        }
                        delete(h) {
                            return h <= 0 ? this : this.push({ delete: h });
                        }
                        retain(h, g) {
                            if (typeof h == "number" && h <= 0) return this;
                            const v = { retain: h };
                            return (
                                g != null &&
                                    typeof g == "object" &&
                                    Object.keys(g).length > 0 &&
                                    (v.attributes = g),
                                this.push(v)
                            );
                        }
                        push(h) {
                            let g = this.ops.length,
                                v = this.ops[g - 1];
                            if (((h = n(h)), typeof v == "object")) {
                                if (
                                    typeof h.delete == "number" &&
                                    typeof v.delete == "number"
                                )
                                    return (
                                        (this.ops[g - 1] = {
                                            delete: v.delete + h.delete,
                                        }),
                                        this
                                    );
                                if (
                                    typeof v.delete == "number" &&
                                    h.insert != null &&
                                    ((g -= 1),
                                    (v = this.ops[g - 1]),
                                    typeof v != "object")
                                )
                                    return (this.ops.unshift(h), this);
                                if (s(h.attributes, v.attributes)) {
                                    if (
                                        typeof h.insert == "string" &&
                                        typeof v.insert == "string"
                                    )
                                        return (
                                            (this.ops[g - 1] = {
                                                insert: v.insert + h.insert,
                                            }),
                                            typeof h.attributes == "object" &&
                                                (this.ops[g - 1].attributes =
                                                    h.attributes),
                                            this
                                        );
                                    if (
                                        typeof h.retain == "number" &&
                                        typeof v.retain == "number"
                                    )
                                        return (
                                            (this.ops[g - 1] = {
                                                retain: v.retain + h.retain,
                                            }),
                                            typeof h.attributes == "object" &&
                                                (this.ops[g - 1].attributes =
                                                    h.attributes),
                                            this
                                        );
                                }
                            }
                            return (
                                g === this.ops.length
                                    ? this.ops.push(h)
                                    : this.ops.splice(g, 0, h),
                                this
                            );
                        }
                        chop() {
                            const h = this.ops[this.ops.length - 1];
                            return (
                                h &&
                                    typeof h.retain == "number" &&
                                    !h.attributes &&
                                    this.ops.pop(),
                                this
                            );
                        }
                        filter(h) {
                            return this.ops.filter(h);
                        }
                        forEach(h) {
                            this.ops.forEach(h);
                        }
                        map(h) {
                            return this.ops.map(h);
                        }
                        partition(h) {
                            const g = [],
                                v = [];
                            return (
                                this.forEach((b) => {
                                    (h(b) ? g : v).push(b);
                                }),
                                [g, v]
                            );
                        }
                        reduce(h, g) {
                            return this.ops.reduce(h, g);
                        }
                        changeLength() {
                            return this.reduce(
                                (h, g) =>
                                    g.insert
                                        ? h + o.default.length(g)
                                        : g.delete
                                          ? h - g.delete
                                          : h,
                                0,
                            );
                        }
                        length() {
                            return this.reduce(
                                (h, g) => h + o.default.length(g),
                                0,
                            );
                        }
                        slice(h = 0, g = 1 / 0) {
                            const v = [],
                                b = new a.default(this.ops);
                            let _ = 0;
                            for (; _ < g && b.hasNext(); ) {
                                let y;
                                (_ < h
                                    ? (y = b.next(h - _))
                                    : ((y = b.next(g - _)), v.push(y)),
                                    (_ += o.default.length(y)));
                            }
                            return new u(v);
                        }
                        compose(h) {
                            const g = new a.default(this.ops),
                                v = new a.default(h.ops),
                                b = [],
                                _ = v.peek();
                            if (
                                _ != null &&
                                typeof _.retain == "number" &&
                                _.attributes == null
                            ) {
                                let S = _.retain;
                                for (
                                    ;
                                    g.peekType() === "insert" &&
                                    g.peekLength() <= S;
                                )
                                    ((S -= g.peekLength()), b.push(g.next()));
                                _.retain - S > 0 && v.next(_.retain - S);
                            }
                            const y = new u(b);
                            for (; g.hasNext() || v.hasNext(); )
                                if (v.peekType() === "insert") y.push(v.next());
                                else if (g.peekType() === "delete")
                                    y.push(g.next());
                                else {
                                    const S = Math.min(
                                            g.peekLength(),
                                            v.peekLength(),
                                        ),
                                        N = g.next(S),
                                        P = v.next(S);
                                    if (P.retain) {
                                        const q = {};
                                        if (typeof N.retain == "number")
                                            q.retain =
                                                typeof P.retain == "number"
                                                    ? S
                                                    : P.retain;
                                        else if (typeof P.retain == "number")
                                            N.retain == null
                                                ? (q.insert = N.insert)
                                                : (q.retain = N.retain);
                                        else {
                                            const te =
                                                    N.retain == null
                                                        ? "insert"
                                                        : "retain",
                                                [re, de, ce] = f(
                                                    N[te],
                                                    P.retain,
                                                ),
                                                w = u.getHandler(re);
                                            q[te] = {
                                                [re]: w.compose(
                                                    de,
                                                    ce,
                                                    te === "retain",
                                                ),
                                            };
                                        }
                                        const F = i.default.compose(
                                            N.attributes,
                                            P.attributes,
                                            typeof N.retain == "number",
                                        );
                                        if (
                                            (F && (q.attributes = F),
                                            y.push(q),
                                            !v.hasNext() &&
                                                s(y.ops[y.ops.length - 1], q))
                                        ) {
                                            const te = new u(g.rest());
                                            return y.concat(te).chop();
                                        }
                                    } else
                                        typeof P.delete == "number" &&
                                            (typeof N.retain == "number" ||
                                                (typeof N.retain == "object" &&
                                                    N.retain !== null)) &&
                                            y.push(P);
                                }
                            return y.chop();
                        }
                        concat(h) {
                            const g = new u(this.ops.slice());
                            return (
                                h.ops.length > 0 &&
                                    (g.push(h.ops[0]),
                                    (g.ops = g.ops.concat(h.ops.slice(1)))),
                                g
                            );
                        }
                        diff(h, g) {
                            if (this.ops === h.ops) return new u();
                            const v = [this, h].map((N) =>
                                    N.map((P) => {
                                        if (P.insert != null)
                                            return typeof P.insert == "string"
                                                ? P.insert
                                                : l;
                                        const q = N === h ? "on" : "with";
                                        throw new Error(
                                            "diff() called " +
                                                q +
                                                " non-document",
                                        );
                                    }).join(""),
                                ),
                                b = new u(),
                                _ = r(v[0], v[1], g, !0),
                                y = new a.default(this.ops),
                                S = new a.default(h.ops);
                            return (
                                _.forEach((N) => {
                                    let P = N[1].length;
                                    for (; P > 0; ) {
                                        let q = 0;
                                        switch (N[0]) {
                                            case r.INSERT:
                                                ((q = Math.min(
                                                    S.peekLength(),
                                                    P,
                                                )),
                                                    b.push(S.next(q)));
                                                break;
                                            case r.DELETE:
                                                ((q = Math.min(
                                                    P,
                                                    y.peekLength(),
                                                )),
                                                    y.next(q),
                                                    b.delete(q));
                                                break;
                                            case r.EQUAL:
                                                q = Math.min(
                                                    y.peekLength(),
                                                    S.peekLength(),
                                                    P,
                                                );
                                                const F = y.next(q),
                                                    te = S.next(q);
                                                s(F.insert, te.insert)
                                                    ? b.retain(
                                                          q,
                                                          i.default.diff(
                                                              F.attributes,
                                                              te.attributes,
                                                          ),
                                                      )
                                                    : b.push(te).delete(q);
                                                break;
                                        }
                                        P -= q;
                                    }
                                }),
                                b.chop()
                            );
                        }
                        eachLine(
                            h,
                            g = `
`,
                        ) {
                            const v = new a.default(this.ops);
                            let b = new u(),
                                _ = 0;
                            for (; v.hasNext(); ) {
                                if (v.peekType() !== "insert") return;
                                const y = v.peek(),
                                    S = o.default.length(y) - v.peekLength(),
                                    N =
                                        typeof y.insert == "string"
                                            ? y.insert.indexOf(g, S) - S
                                            : -1;
                                if (N < 0) b.push(v.next());
                                else if (N > 0) b.push(v.next(N));
                                else {
                                    if (
                                        h(b, v.next(1).attributes || {}, _) ===
                                        !1
                                    )
                                        return;
                                    ((_ += 1), (b = new u()));
                                }
                            }
                            b.length() > 0 && h(b, {}, _);
                        }
                        invert(h) {
                            const g = new u();
                            return (
                                this.reduce((v, b) => {
                                    if (b.insert) g.delete(o.default.length(b));
                                    else {
                                        if (
                                            typeof b.retain == "number" &&
                                            b.attributes == null
                                        )
                                            return (
                                                g.retain(b.retain),
                                                v + b.retain
                                            );
                                        if (
                                            b.delete ||
                                            typeof b.retain == "number"
                                        ) {
                                            const _ = b.delete || b.retain;
                                            return (
                                                h
                                                    .slice(v, v + _)
                                                    .forEach((S) => {
                                                        b.delete
                                                            ? g.push(S)
                                                            : b.retain &&
                                                              b.attributes &&
                                                              g.retain(
                                                                  o.default.length(
                                                                      S,
                                                                  ),
                                                                  i.default.invert(
                                                                      b.attributes,
                                                                      S.attributes,
                                                                  ),
                                                              );
                                                    }),
                                                v + _
                                            );
                                        } else if (
                                            typeof b.retain == "object" &&
                                            b.retain !== null
                                        ) {
                                            const _ = h.slice(v, v + 1),
                                                y = new a.default(_.ops).next(),
                                                [S, N, P] = f(
                                                    b.retain,
                                                    y.insert,
                                                ),
                                                q = u.getHandler(S);
                                            return (
                                                g.retain(
                                                    { [S]: q.invert(N, P) },
                                                    i.default.invert(
                                                        b.attributes,
                                                        y.attributes,
                                                    ),
                                                ),
                                                v + 1
                                            );
                                        }
                                    }
                                    return v;
                                }, 0),
                                g.chop()
                            );
                        }
                        transform(h, g = !1) {
                            if (((g = !!g), typeof h == "number"))
                                return this.transformPosition(h, g);
                            const v = h,
                                b = new a.default(this.ops),
                                _ = new a.default(v.ops),
                                y = new u();
                            for (; b.hasNext() || _.hasNext(); )
                                if (
                                    b.peekType() === "insert" &&
                                    (g || _.peekType() !== "insert")
                                )
                                    y.retain(o.default.length(b.next()));
                                else if (_.peekType() === "insert")
                                    y.push(_.next());
                                else {
                                    const S = Math.min(
                                            b.peekLength(),
                                            _.peekLength(),
                                        ),
                                        N = b.next(S),
                                        P = _.next(S);
                                    if (N.delete) continue;
                                    if (P.delete) y.push(P);
                                    else {
                                        const q = N.retain,
                                            F = P.retain;
                                        let te =
                                            typeof F == "object" && F !== null
                                                ? F
                                                : S;
                                        if (
                                            typeof q == "object" &&
                                            q !== null &&
                                            typeof F == "object" &&
                                            F !== null
                                        ) {
                                            const re = Object.keys(q)[0];
                                            if (re === Object.keys(F)[0]) {
                                                const de = u.getHandler(re);
                                                de &&
                                                    (te = {
                                                        [re]: de.transform(
                                                            q[re],
                                                            F[re],
                                                            g,
                                                        ),
                                                    });
                                            }
                                        }
                                        y.retain(
                                            te,
                                            i.default.transform(
                                                N.attributes,
                                                P.attributes,
                                                g,
                                            ),
                                        );
                                    }
                                }
                            return y.chop();
                        }
                        transformPosition(h, g = !1) {
                            g = !!g;
                            const v = new a.default(this.ops);
                            let b = 0;
                            for (; v.hasNext() && b <= h; ) {
                                const _ = v.peekLength(),
                                    y = v.peekType();
                                if ((v.next(), y === "delete")) {
                                    h -= Math.min(_, h - b);
                                    continue;
                                } else
                                    y === "insert" && (b < h || !g) && (h += _);
                                b += _;
                            }
                            return h;
                        }
                    }
                    ((u.Op = o.default),
                        (u.OpIterator = a.default),
                        (u.AttributeMap = i.default),
                        (u.handlers = {}),
                        (e.default = u),
                        (t.exports = u),
                        (t.exports.default = u));
                })(Ls, Ls.exports)),
            Ls.exports
        );
    }
    var Ze = aw();
    const z = hu(Ze);
    class ft extends He {
        static value() {}
        optimize() {
            (this.prev || this.next) && this.remove();
        }
        length() {
            return 0;
        }
        value() {
            return "";
        }
    }
    ft.blotName = "break";
    ft.tagName = "BR";
    let it = class extends ei {};
    const lw = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    };
    function yi(t) {
        return t.replace(/[&<>"']/g, (e) => lw[e]);
    }
    class Pe extends Ya {
        static allowedChildren = [Pe, ft, He, it];
        static order = [
            "cursor",
            "inline",
            "link",
            "underline",
            "strike",
            "italic",
            "bold",
            "script",
            "code",
        ];
        static compare(e, r) {
            const n = Pe.order.indexOf(e),
                s = Pe.order.indexOf(r);
            return n >= 0 || s >= 0 ? n - s : e === r ? 0 : e < r ? -1 : 1;
        }
        formatAt(e, r, n, s) {
            if (
                Pe.compare(this.statics.blotName, n) < 0 &&
                this.scroll.query(n, K.BLOT)
            ) {
                const i = this.isolate(e, r);
                s && i.wrap(n, s);
            } else super.formatAt(e, r, n, s);
        }
        optimize(e) {
            if (
                (super.optimize(e),
                this.parent instanceof Pe &&
                    Pe.compare(
                        this.statics.blotName,
                        this.parent.statics.blotName,
                    ) > 0)
            ) {
                const r = this.parent.isolate(this.offset(), this.length());
                (this.moveChildren(r), r.wrap(this));
            }
        }
    }
    const S0 = 1;
    class Ne extends Wn {
        cache = {};
        delta() {
            return (
                this.cache.delta == null && (this.cache.delta = mu(this)),
                this.cache.delta
            );
        }
        deleteAt(e, r) {
            (super.deleteAt(e, r), (this.cache = {}));
        }
        formatAt(e, r, n, s) {
            r <= 0 ||
                (this.scroll.query(n, K.BLOCK)
                    ? e + r === this.length() && this.format(n, s)
                    : super.formatAt(
                          e,
                          Math.min(r, this.length() - e - 1),
                          n,
                          s,
                      ),
                (this.cache = {}));
        }
        insertAt(e, r, n) {
            if (n != null) {
                (super.insertAt(e, r, n), (this.cache = {}));
                return;
            }
            if (r.length === 0) return;
            const s = r.split(`
`),
                i = s.shift();
            i.length > 0 &&
                (e < this.length() - 1 || this.children.tail == null
                    ? super.insertAt(Math.min(e, this.length() - 1), i)
                    : this.children.tail.insertAt(
                          this.children.tail.length(),
                          i,
                      ),
                (this.cache = {}));
            let o = this;
            s.reduce(
                (a, l) => ((o = o.split(a, !0)), o.insertAt(0, l), l.length),
                e + i.length,
            );
        }
        insertBefore(e, r) {
            const { head: n } = this.children;
            (super.insertBefore(e, r),
                n instanceof ft && n.remove(),
                (this.cache = {}));
        }
        length() {
            return (
                this.cache.length == null &&
                    (this.cache.length = super.length() + S0),
                this.cache.length
            );
        }
        moveChildren(e, r) {
            (super.moveChildren(e, r), (this.cache = {}));
        }
        optimize(e) {
            (super.optimize(e), (this.cache = {}));
        }
        path(e) {
            return super.path(e, !0);
        }
        removeChild(e) {
            (super.removeChild(e), (this.cache = {}));
        }
        split(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : !1;
            if (r && (e === 0 || e >= this.length() - S0)) {
                const s = this.clone();
                return e === 0
                    ? (this.parent.insertBefore(s, this), this)
                    : (this.parent.insertBefore(s, this.next), s);
            }
            const n = super.split(e, r);
            return ((this.cache = {}), n);
        }
    }
    Ne.blotName = "block";
    Ne.tagName = "P";
    Ne.defaultChild = ft;
    Ne.allowedChildren = [ft, Pe, He, it];
    class Ge extends He {
        attach() {
            (super.attach(), (this.attributes = new mi(this.domNode)));
        }
        delta() {
            return new z().insert(this.value(), {
                ...this.formats(),
                ...this.attributes.values(),
            });
        }
        format(e, r) {
            const n = this.scroll.query(e, K.BLOCK_ATTRIBUTE);
            n != null && this.attributes.attribute(n, r);
        }
        formatAt(e, r, n, s) {
            this.format(n, s);
        }
        insertAt(e, r, n) {
            if (n != null) {
                super.insertAt(e, r, n);
                return;
            }
            const s = r.split(`
`),
                i = s.pop(),
                o = s.map((l) => {
                    const f = this.scroll.create(Ne.blotName);
                    return (f.insertAt(0, l), f);
                }),
                a = this.split(e);
            (o.forEach((l) => {
                this.parent.insertBefore(l, a);
            }),
                i &&
                    this.parent.insertBefore(this.scroll.create("text", i), a));
        }
    }
    Ge.scope = K.BLOCK_BLOT;
    function mu(t) {
        let e =
            arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : !0;
        return t
            .descendants(qe)
            .reduce(
                (r, n) =>
                    n.length() === 0 ? r : r.insert(n.value(), We(n, {}, e)),
                new z(),
            )
            .insert(
                `
`,
                We(t),
            );
    }
    function We(t) {
        let e =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : {},
            r =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : !0;
        return t == null ||
            ("formats" in t &&
                typeof t.formats == "function" &&
                ((e = { ...e, ...t.formats() }), r && delete e["code-token"]),
            t.parent == null ||
                t.parent.statics.blotName === "scroll" ||
                t.parent.statics.scope !== t.statics.scope)
            ? e
            : We(t.parent, e, r);
    }
    class tt extends He {
        static blotName = "cursor";
        static className = "ql-cursor";
        static tagName = "span";
        static CONTENTS = "\uFEFF";
        static value() {}
        constructor(e, r, n) {
            (super(e, r),
                (this.selection = n),
                (this.textNode = document.createTextNode(tt.CONTENTS)),
                this.domNode.appendChild(this.textNode),
                (this.savedLength = 0));
        }
        detach() {
            this.parent != null && this.parent.removeChild(this);
        }
        format(e, r) {
            if (this.savedLength !== 0) {
                super.format(e, r);
                return;
            }
            let n = this,
                s = 0;
            for (; n != null && n.statics.scope !== K.BLOCK_BLOT; )
                ((s += n.offset(n.parent)), (n = n.parent));
            n != null &&
                ((this.savedLength = tt.CONTENTS.length),
                n.optimize(),
                n.formatAt(s, tt.CONTENTS.length, e, r),
                (this.savedLength = 0));
        }
        index(e, r) {
            return e === this.textNode ? 0 : super.index(e, r);
        }
        length() {
            return this.savedLength;
        }
        position() {
            return [this.textNode, this.textNode.data.length];
        }
        remove() {
            (super.remove(), (this.parent = null));
        }
        restore() {
            if (this.selection.composing || this.parent == null) return null;
            const e = this.selection.getNativeRange();
            for (
                ;
                this.domNode.lastChild != null &&
                this.domNode.lastChild !== this.textNode;
            )
                this.domNode.parentNode.insertBefore(
                    this.domNode.lastChild,
                    this.domNode,
                );
            const r = this.prev instanceof it ? this.prev : null,
                n = r ? r.length() : 0,
                s = this.next instanceof it ? this.next : null,
                i = s ? s.text : "",
                { textNode: o } = this,
                a = o.data.split(tt.CONTENTS).join("");
            o.data = tt.CONTENTS;
            let l;
            if (r)
                ((l = r),
                    (a || s) &&
                        (r.insertAt(r.length(), a + i), s && s.remove()));
            else if (s) ((l = s), s.insertAt(0, a));
            else {
                const f = document.createTextNode(a);
                ((l = this.scroll.create(f)),
                    this.parent.insertBefore(l, this));
            }
            if ((this.remove(), e)) {
                const f = (h, g) =>
                        r && h === r.domNode
                            ? g
                            : h === o
                              ? n + g - 1
                              : s && h === s.domNode
                                ? n + a.length + g
                                : null,
                    u = f(e.start.node, e.start.offset),
                    d = f(e.end.node, e.end.offset);
                if (u !== null && d !== null)
                    return {
                        startNode: l.domNode,
                        startOffset: u,
                        endNode: l.domNode,
                        endOffset: d,
                    };
            }
            return null;
        }
        update(e, r) {
            if (
                e.some(
                    (n) =>
                        n.type === "characterData" &&
                        n.target === this.textNode,
                )
            ) {
                const n = this.restore();
                n && (r.range = n);
            }
        }
        optimize(e) {
            super.optimize(e);
            let { parent: r } = this;
            for (; r; ) {
                if (r.domNode.tagName === "A") {
                    ((this.savedLength = tt.CONTENTS.length),
                        r.isolate(this.offset(r), this.length()).unwrap(),
                        (this.savedLength = 0));
                    break;
                }
                r = r.parent;
            }
        }
        value() {
            return "";
        }
    }
    var _o = { exports: {} },
        T0;
    function fw() {
        return (
            T0 ||
                ((T0 = 1),
                (function (t) {
                    var e = Object.prototype.hasOwnProperty,
                        r = "~";
                    function n() {}
                    Object.create &&
                        ((n.prototype = Object.create(null)),
                        new n().__proto__ || (r = !1));
                    function s(l, f, u) {
                        ((this.fn = l),
                            (this.context = f),
                            (this.once = u || !1));
                    }
                    function i(l, f, u, d, h) {
                        if (typeof u != "function")
                            throw new TypeError(
                                "The listener must be a function",
                            );
                        var g = new s(u, d || l, h),
                            v = r ? r + f : f;
                        return (
                            l._events[v]
                                ? l._events[v].fn
                                    ? (l._events[v] = [l._events[v], g])
                                    : l._events[v].push(g)
                                : ((l._events[v] = g), l._eventsCount++),
                            l
                        );
                    }
                    function o(l, f) {
                        --l._eventsCount === 0
                            ? (l._events = new n())
                            : delete l._events[f];
                    }
                    function a() {
                        ((this._events = new n()), (this._eventsCount = 0));
                    }
                    ((a.prototype.eventNames = function () {
                        var f = [],
                            u,
                            d;
                        if (this._eventsCount === 0) return f;
                        for (d in (u = this._events))
                            e.call(u, d) && f.push(r ? d.slice(1) : d);
                        return Object.getOwnPropertySymbols
                            ? f.concat(Object.getOwnPropertySymbols(u))
                            : f;
                    }),
                        (a.prototype.listeners = function (f) {
                            var u = r ? r + f : f,
                                d = this._events[u];
                            if (!d) return [];
                            if (d.fn) return [d.fn];
                            for (
                                var h = 0, g = d.length, v = new Array(g);
                                h < g;
                                h++
                            )
                                v[h] = d[h].fn;
                            return v;
                        }),
                        (a.prototype.listenerCount = function (f) {
                            var u = r ? r + f : f,
                                d = this._events[u];
                            return d ? (d.fn ? 1 : d.length) : 0;
                        }),
                        (a.prototype.emit = function (f, u, d, h, g, v) {
                            var b = r ? r + f : f;
                            if (!this._events[b]) return !1;
                            var _ = this._events[b],
                                y = arguments.length,
                                S,
                                N;
                            if (_.fn) {
                                switch (
                                    (_.once &&
                                        this.removeListener(
                                            f,
                                            _.fn,
                                            void 0,
                                            !0,
                                        ),
                                    y)
                                ) {
                                    case 1:
                                        return (_.fn.call(_.context), !0);
                                    case 2:
                                        return (_.fn.call(_.context, u), !0);
                                    case 3:
                                        return (_.fn.call(_.context, u, d), !0);
                                    case 4:
                                        return (
                                            _.fn.call(_.context, u, d, h),
                                            !0
                                        );
                                    case 5:
                                        return (
                                            _.fn.call(_.context, u, d, h, g),
                                            !0
                                        );
                                    case 6:
                                        return (
                                            _.fn.call(_.context, u, d, h, g, v),
                                            !0
                                        );
                                }
                                for (N = 1, S = new Array(y - 1); N < y; N++)
                                    S[N - 1] = arguments[N];
                                _.fn.apply(_.context, S);
                            } else {
                                var P = _.length,
                                    q;
                                for (N = 0; N < P; N++)
                                    switch (
                                        (_[N].once &&
                                            this.removeListener(
                                                f,
                                                _[N].fn,
                                                void 0,
                                                !0,
                                            ),
                                        y)
                                    ) {
                                        case 1:
                                            _[N].fn.call(_[N].context);
                                            break;
                                        case 2:
                                            _[N].fn.call(_[N].context, u);
                                            break;
                                        case 3:
                                            _[N].fn.call(_[N].context, u, d);
                                            break;
                                        case 4:
                                            _[N].fn.call(_[N].context, u, d, h);
                                            break;
                                        default:
                                            if (!S)
                                                for (
                                                    q = 1, S = new Array(y - 1);
                                                    q < y;
                                                    q++
                                                )
                                                    S[q - 1] = arguments[q];
                                            _[N].fn.apply(_[N].context, S);
                                    }
                            }
                            return !0;
                        }),
                        (a.prototype.on = function (f, u, d) {
                            return i(this, f, u, d, !1);
                        }),
                        (a.prototype.once = function (f, u, d) {
                            return i(this, f, u, d, !0);
                        }),
                        (a.prototype.removeListener = function (f, u, d, h) {
                            var g = r ? r + f : f;
                            if (!this._events[g]) return this;
                            if (!u) return (o(this, g), this);
                            var v = this._events[g];
                            if (v.fn)
                                v.fn === u &&
                                    (!h || v.once) &&
                                    (!d || v.context === d) &&
                                    o(this, g);
                            else {
                                for (
                                    var b = 0, _ = [], y = v.length;
                                    b < y;
                                    b++
                                )
                                    (v[b].fn !== u ||
                                        (h && !v[b].once) ||
                                        (d && v[b].context !== d)) &&
                                        _.push(v[b]);
                                _.length
                                    ? (this._events[g] =
                                          _.length === 1 ? _[0] : _)
                                    : o(this, g);
                            }
                            return this;
                        }),
                        (a.prototype.removeAllListeners = function (f) {
                            var u;
                            return (
                                f
                                    ? ((u = r ? r + f : f),
                                      this._events[u] && o(this, u))
                                    : ((this._events = new n()),
                                      (this._eventsCount = 0)),
                                this
                            );
                        }),
                        (a.prototype.off = a.prototype.removeListener),
                        (a.prototype.addListener = a.prototype.on),
                        (a.prefixed = r),
                        (a.EventEmitter = a),
                        (t.exports = a));
                })(_o)),
            _o.exports
        );
    }
    var cw = fw();
    const uw = hu(cw),
        fa = new WeakMap(),
        ca = ["error", "warn", "log", "info"];
    let ua = "warn";
    function bu(t) {
        if (ua && ca.indexOf(t) <= ca.indexOf(ua)) {
            for (
                var e = arguments.length,
                    r = new Array(e > 1 ? e - 1 : 0),
                    n = 1;
                n < e;
                n++
            )
                r[n - 1] = arguments[n];
            console[t](...r);
        }
    }
    function qt(t) {
        return ca.reduce((e, r) => ((e[r] = bu.bind(console, r, t)), e), {});
    }
    qt.level = (t) => {
        ua = t;
    };
    bu.level = qt.level;
    const xo = qt("quill:events"),
        hw = ["selectionchange", "mousedown", "mouseup", "click"];
    hw.forEach((t) => {
        document.addEventListener(t, function () {
            for (var e = arguments.length, r = new Array(e), n = 0; n < e; n++)
                r[n] = arguments[n];
            Array.from(document.querySelectorAll(".ql-container")).forEach(
                (s) => {
                    const i = fa.get(s);
                    i && i.emitter && i.emitter.handleDOM(...r);
                },
            );
        });
    });
    class H extends uw {
        static events = {
            EDITOR_CHANGE: "editor-change",
            SCROLL_BEFORE_UPDATE: "scroll-before-update",
            SCROLL_BLOT_MOUNT: "scroll-blot-mount",
            SCROLL_BLOT_UNMOUNT: "scroll-blot-unmount",
            SCROLL_OPTIMIZE: "scroll-optimize",
            SCROLL_UPDATE: "scroll-update",
            SCROLL_EMBED_UPDATE: "scroll-embed-update",
            SELECTION_CHANGE: "selection-change",
            TEXT_CHANGE: "text-change",
            COMPOSITION_BEFORE_START: "composition-before-start",
            COMPOSITION_START: "composition-start",
            COMPOSITION_BEFORE_END: "composition-before-end",
            COMPOSITION_END: "composition-end",
        };
        static sources = { API: "api", SILENT: "silent", USER: "user" };
        constructor() {
            (super(), (this.domListeners = {}), this.on("error", xo.error));
        }
        emit() {
            for (var e = arguments.length, r = new Array(e), n = 0; n < e; n++)
                r[n] = arguments[n];
            return (xo.log.call(xo, ...r), super.emit(...r));
        }
        handleDOM(e) {
            for (
                var r = arguments.length,
                    n = new Array(r > 1 ? r - 1 : 0),
                    s = 1;
                s < r;
                s++
            )
                n[s - 1] = arguments[s];
            (this.domListeners[e.type] || []).forEach((i) => {
                let { node: o, handler: a } = i;
                (e.target === o || o.contains(e.target)) && a(e, ...n);
            });
        }
        listenDOM(e, r, n) {
            (this.domListeners[e] || (this.domListeners[e] = []),
                this.domListeners[e].push({ node: r, handler: n }));
        }
    }
    const Ao = qt("quill:selection");
    class yr {
        constructor(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : 0;
            ((this.index = e), (this.length = r));
        }
    }
    class dw {
        constructor(e, r) {
            ((this.emitter = r),
                (this.scroll = e),
                (this.composing = !1),
                (this.mouseDown = !1),
                (this.root = this.scroll.domNode),
                (this.cursor = this.scroll.create("cursor", this)),
                (this.savedRange = new yr(0, 0)),
                (this.lastRange = this.savedRange),
                (this.lastNative = null),
                this.handleComposition(),
                this.handleDragging(),
                this.emitter.listenDOM("selectionchange", document, () => {
                    !this.mouseDown &&
                        !this.composing &&
                        setTimeout(this.update.bind(this, H.sources.USER), 1);
                }),
                this.emitter.on(H.events.SCROLL_BEFORE_UPDATE, () => {
                    if (!this.hasFocus()) return;
                    const n = this.getNativeRange();
                    n != null &&
                        n.start.node !== this.cursor.textNode &&
                        this.emitter.once(H.events.SCROLL_UPDATE, (s, i) => {
                            try {
                                this.root.contains(n.start.node) &&
                                    this.root.contains(n.end.node) &&
                                    this.setNativeRange(
                                        n.start.node,
                                        n.start.offset,
                                        n.end.node,
                                        n.end.offset,
                                    );
                                const o = i.some(
                                    (a) =>
                                        a.type === "characterData" ||
                                        a.type === "childList" ||
                                        (a.type === "attributes" &&
                                            a.target === this.root),
                                );
                                this.update(o ? H.sources.SILENT : s);
                            } catch {}
                        });
                }),
                this.emitter.on(H.events.SCROLL_OPTIMIZE, (n, s) => {
                    if (s.range) {
                        const {
                            startNode: i,
                            startOffset: o,
                            endNode: a,
                            endOffset: l,
                        } = s.range;
                        (this.setNativeRange(i, o, a, l),
                            this.update(H.sources.SILENT));
                    }
                }),
                this.update(H.sources.SILENT));
        }
        handleComposition() {
            (this.emitter.on(H.events.COMPOSITION_BEFORE_START, () => {
                this.composing = !0;
            }),
                this.emitter.on(H.events.COMPOSITION_END, () => {
                    if (((this.composing = !1), this.cursor.parent)) {
                        const e = this.cursor.restore();
                        if (!e) return;
                        setTimeout(() => {
                            this.setNativeRange(
                                e.startNode,
                                e.startOffset,
                                e.endNode,
                                e.endOffset,
                            );
                        }, 1);
                    }
                }));
        }
        handleDragging() {
            (this.emitter.listenDOM("mousedown", document.body, () => {
                this.mouseDown = !0;
            }),
                this.emitter.listenDOM("mouseup", document.body, () => {
                    ((this.mouseDown = !1), this.update(H.sources.USER));
                }));
        }
        focus() {
            this.hasFocus() ||
                (this.root.focus({ preventScroll: !0 }),
                this.setRange(this.savedRange));
        }
        format(e, r) {
            this.scroll.update();
            const n = this.getNativeRange();
            if (
                !(
                    n == null ||
                    !n.native.collapsed ||
                    this.scroll.query(e, K.BLOCK)
                )
            ) {
                if (n.start.node !== this.cursor.textNode) {
                    const s = this.scroll.find(n.start.node, !1);
                    if (s == null) return;
                    if (s instanceof qe) {
                        const i = s.split(n.start.offset);
                        s.parent.insertBefore(this.cursor, i);
                    } else s.insertBefore(this.cursor, n.start.node);
                    this.cursor.attach();
                }
                (this.cursor.format(e, r),
                    this.scroll.optimize(),
                    this.setNativeRange(
                        this.cursor.textNode,
                        this.cursor.textNode.data.length,
                    ),
                    this.update());
            }
        }
        getBounds(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : 0;
            const n = this.scroll.length();
            ((e = Math.min(e, n - 1)), (r = Math.min(e + r, n - 1) - e));
            let s,
                [i, o] = this.scroll.leaf(e);
            if (i == null) return null;
            if (r > 0 && o === i.length()) {
                const [u] = this.scroll.leaf(e + 1);
                if (u) {
                    const [d] = this.scroll.line(e),
                        [h] = this.scroll.line(e + 1);
                    d === h && ((i = u), (o = 0));
                }
            }
            [s, o] = i.position(o, !0);
            const a = document.createRange();
            if (r > 0)
                return (
                    a.setStart(s, o),
                    ([i, o] = this.scroll.leaf(e + r)),
                    i == null
                        ? null
                        : (([s, o] = i.position(o, !0)),
                          a.setEnd(s, o),
                          a.getBoundingClientRect())
                );
            let l = "left",
                f;
            if (s instanceof Text) {
                if (!s.data.length) return null;
                (o < s.data.length
                    ? (a.setStart(s, o), a.setEnd(s, o + 1))
                    : (a.setStart(s, o - 1), a.setEnd(s, o), (l = "right")),
                    (f = a.getBoundingClientRect()));
            } else {
                if (!(i.domNode instanceof Element)) return null;
                ((f = i.domNode.getBoundingClientRect()),
                    o > 0 && (l = "right"));
            }
            return {
                bottom: f.top + f.height,
                height: f.height,
                left: f[l],
                right: f[l],
                top: f.top,
                width: 0,
            };
        }
        getNativeRange() {
            const e = document.getSelection();
            if (e == null || e.rangeCount <= 0) return null;
            const r = e.getRangeAt(0);
            if (r == null) return null;
            const n = this.normalizeNative(r);
            return (Ao.info("getNativeRange", n), n);
        }
        getRange() {
            const e = this.scroll.domNode;
            if ("isConnected" in e && !e.isConnected) return [null, null];
            const r = this.getNativeRange();
            return r == null ? [null, null] : [this.normalizedToRange(r), r];
        }
        hasFocus() {
            return (
                document.activeElement === this.root ||
                (document.activeElement != null &&
                    So(this.root, document.activeElement))
            );
        }
        normalizedToRange(e) {
            const r = [[e.start.node, e.start.offset]];
            e.native.collapsed || r.push([e.end.node, e.end.offset]);
            const n = r.map((o) => {
                    const [a, l] = o,
                        f = this.scroll.find(a, !0),
                        u = f.offset(this.scroll);
                    return l === 0
                        ? u
                        : f instanceof qe
                          ? u + f.index(a, l)
                          : u + f.length();
                }),
                s = Math.min(Math.max(...n), this.scroll.length() - 1),
                i = Math.min(s, ...n);
            return new yr(i, s - i);
        }
        normalizeNative(e) {
            if (
                !So(this.root, e.startContainer) ||
                (!e.collapsed && !So(this.root, e.endContainer))
            )
                return null;
            const r = {
                start: { node: e.startContainer, offset: e.startOffset },
                end: { node: e.endContainer, offset: e.endOffset },
                native: e,
            };
            return (
                [r.start, r.end].forEach((n) => {
                    let { node: s, offset: i } = n;
                    for (; !(s instanceof Text) && s.childNodes.length > 0; )
                        if (s.childNodes.length > i)
                            ((s = s.childNodes[i]), (i = 0));
                        else if (s.childNodes.length === i)
                            ((s = s.lastChild),
                                s instanceof Text
                                    ? (i = s.data.length)
                                    : s.childNodes.length > 0
                                      ? (i = s.childNodes.length)
                                      : (i = s.childNodes.length + 1));
                        else break;
                    ((n.node = s), (n.offset = i));
                }),
                r
            );
        }
        rangeToNative(e) {
            const r = this.scroll.length(),
                n = (s, i) => {
                    s = Math.min(r - 1, s);
                    const [o, a] = this.scroll.leaf(s);
                    return o ? o.position(a, i) : [null, -1];
                };
            return [...n(e.index, !1), ...n(e.index + e.length, !0)];
        }
        setNativeRange(e, r) {
            let n =
                    arguments.length > 2 && arguments[2] !== void 0
                        ? arguments[2]
                        : e,
                s =
                    arguments.length > 3 && arguments[3] !== void 0
                        ? arguments[3]
                        : r,
                i =
                    arguments.length > 4 && arguments[4] !== void 0
                        ? arguments[4]
                        : !1;
            if (
                (Ao.info("setNativeRange", e, r, n, s),
                e != null &&
                    (this.root.parentNode == null ||
                        e.parentNode == null ||
                        n.parentNode == null))
            )
                return;
            const o = document.getSelection();
            if (o != null)
                if (e != null) {
                    this.hasFocus() || this.root.focus({ preventScroll: !0 });
                    const { native: a } = this.getNativeRange() || {};
                    if (
                        a == null ||
                        i ||
                        e !== a.startContainer ||
                        r !== a.startOffset ||
                        n !== a.endContainer ||
                        s !== a.endOffset
                    ) {
                        (e instanceof Element &&
                            e.tagName === "BR" &&
                            ((r = Array.from(e.parentNode.childNodes).indexOf(
                                e,
                            )),
                            (e = e.parentNode)),
                            n instanceof Element &&
                                n.tagName === "BR" &&
                                ((s = Array.from(
                                    n.parentNode.childNodes,
                                ).indexOf(n)),
                                (n = n.parentNode)));
                        const l = document.createRange();
                        (l.setStart(e, r),
                            l.setEnd(n, s),
                            o.removeAllRanges(),
                            o.addRange(l));
                    }
                } else (o.removeAllRanges(), this.root.blur());
        }
        setRange(e) {
            let r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : !1,
                n =
                    arguments.length > 2 && arguments[2] !== void 0
                        ? arguments[2]
                        : H.sources.API;
            if (
                (typeof r == "string" && ((n = r), (r = !1)),
                Ao.info("setRange", e),
                e != null)
            ) {
                const s = this.rangeToNative(e);
                this.setNativeRange(...s, r);
            } else this.setNativeRange(null);
            this.update(n);
        }
        update() {
            let e =
                arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : H.sources.USER;
            const r = this.lastRange,
                [n, s] = this.getRange();
            if (
                ((this.lastRange = n),
                (this.lastNative = s),
                this.lastRange != null && (this.savedRange = this.lastRange),
                !Ja(r, this.lastRange))
            ) {
                if (
                    !this.composing &&
                    s != null &&
                    s.native.collapsed &&
                    s.start.node !== this.cursor.textNode
                ) {
                    const o = this.cursor.restore();
                    o &&
                        this.setNativeRange(
                            o.startNode,
                            o.startOffset,
                            o.endNode,
                            o.endOffset,
                        );
                }
                const i = [
                    H.events.SELECTION_CHANGE,
                    Kr(this.lastRange),
                    Kr(r),
                    e,
                ];
                (this.emitter.emit(H.events.EDITOR_CHANGE, ...i),
                    e !== H.sources.SILENT && this.emitter.emit(...i));
            }
        }
    }
    function So(t, e) {
        try {
            e.parentNode;
        } catch {
            return !1;
        }
        return t.contains(e);
    }
    const pw = /^[ -~]*$/;
    class gw {
        constructor(e) {
            ((this.scroll = e), (this.delta = this.getDelta()));
        }
        applyDelta(e) {
            this.scroll.update();
            let r = this.scroll.length();
            this.scroll.batchStart();
            const n = N0(e),
                s = new z();
            return (
                bw(n.ops.slice()).reduce((o, a) => {
                    const l = Ze.Op.length(a);
                    let f = a.attributes || {},
                        u = !1,
                        d = !1;
                    if (a.insert != null) {
                        if ((s.retain(l), typeof a.insert == "string")) {
                            const v = a.insert;
                            ((d =
                                !v.endsWith(`
`) &&
                                (r <= o || !!this.scroll.descendant(Ge, o)[0])),
                                this.scroll.insertAt(o, v));
                            const [b, _] = this.scroll.line(o);
                            let y = jt({}, We(b));
                            if (b instanceof Ne) {
                                const [S] = b.descendant(qe, _);
                                S && (y = jt(y, We(S)));
                            }
                            f = Ze.AttributeMap.diff(y, f) || {};
                        } else if (typeof a.insert == "object") {
                            const v = Object.keys(a.insert)[0];
                            if (v == null) return o;
                            const b = this.scroll.query(v, K.INLINE) != null;
                            if (b)
                                (r <= o || this.scroll.descendant(Ge, o)[0]) &&
                                    (d = !0);
                            else if (o > 0) {
                                const [_, y] = this.scroll.descendant(
                                    qe,
                                    o - 1,
                                );
                                _ instanceof it
                                    ? _.value()[y] !==
                                          `
` && (u = !0)
                                    : _ instanceof He &&
                                      _.statics.scope === K.INLINE_BLOT &&
                                      (u = !0);
                            }
                            if ((this.scroll.insertAt(o, v, a.insert[v]), b)) {
                                const [_] = this.scroll.descendant(qe, o);
                                if (_) {
                                    const y = jt({}, We(_));
                                    f = Ze.AttributeMap.diff(y, f) || {};
                                }
                            }
                        }
                        r += l;
                    } else if (
                        (s.push(a),
                        a.retain !== null && typeof a.retain == "object")
                    ) {
                        const v = Object.keys(a.retain)[0];
                        if (v == null) return o;
                        this.scroll.updateEmbedAt(o, v, a.retain[v]);
                    }
                    Object.keys(f).forEach((v) => {
                        this.scroll.formatAt(o, l, v, f[v]);
                    });
                    const h = u ? 1 : 0,
                        g = d ? 1 : 0;
                    return (
                        (r += h + g),
                        s.retain(h),
                        s.delete(g),
                        o + l + h + g
                    );
                }, 0),
                s.reduce(
                    (o, a) =>
                        typeof a.delete == "number"
                            ? (this.scroll.deleteAt(o, a.delete), o)
                            : o + Ze.Op.length(a),
                    0,
                ),
                this.scroll.batchEnd(),
                this.scroll.optimize(),
                this.update(n)
            );
        }
        deleteText(e, r) {
            return (
                this.scroll.deleteAt(e, r),
                this.update(new z().retain(e).delete(r))
            );
        }
        formatLine(e, r) {
            let n =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : {};
            (this.scroll.update(),
                Object.keys(n).forEach((i) => {
                    this.scroll.lines(e, Math.max(r, 1)).forEach((o) => {
                        o.format(i, n[i]);
                    });
                }),
                this.scroll.optimize());
            const s = new z().retain(e).retain(r, Kr(n));
            return this.update(s);
        }
        formatText(e, r) {
            let n =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : {};
            Object.keys(n).forEach((i) => {
                this.scroll.formatAt(e, r, i, n[i]);
            });
            const s = new z().retain(e).retain(r, Kr(n));
            return this.update(s);
        }
        getContents(e, r) {
            return this.delta.slice(e, e + r);
        }
        getDelta() {
            return this.scroll
                .lines()
                .reduce((e, r) => e.concat(r.delta()), new z());
        }
        getFormat(e) {
            let r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : 0,
                n = [],
                s = [];
            r === 0
                ? this.scroll.path(e).forEach((a) => {
                      const [l] = a;
                      l instanceof Ne
                          ? n.push(l)
                          : l instanceof qe && s.push(l);
                  })
                : ((n = this.scroll.lines(e, r)),
                  (s = this.scroll.descendants(qe, e, r)));
            const [i, o] = [n, s].map((a) => {
                const l = a.shift();
                if (l == null) return {};
                let f = We(l);
                for (; Object.keys(f).length > 0; ) {
                    const u = a.shift();
                    if (u == null) return f;
                    f = mw(We(u), f);
                }
                return f;
            });
            return { ...i, ...o };
        }
        getHTML(e, r) {
            const [n, s] = this.scroll.line(e);
            if (n) {
                const i = n.length();
                return n.length() >= s + r && !(s === 0 && r === i)
                    ? Vn(n, s, r, !0)
                    : Vn(this.scroll, e, r, !0);
            }
            return "";
        }
        getText(e, r) {
            return this.getContents(e, r)
                .filter((n) => typeof n.insert == "string")
                .map((n) => n.insert)
                .join("");
        }
        insertContents(e, r) {
            const n = N0(r),
                s = new z().retain(e).concat(n);
            return (this.scroll.insertContents(e, n), this.update(s));
        }
        insertEmbed(e, r, n) {
            return (
                this.scroll.insertAt(e, r, n),
                this.update(new z().retain(e).insert({ [r]: n }))
            );
        }
        insertText(e, r) {
            let n =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : {};
            return (
                (r = r
                    .replace(
                        /\r\n/g,
                        `
`,
                    )
                    .replace(
                        /\r/g,
                        `
`,
                    )),
                this.scroll.insertAt(e, r),
                Object.keys(n).forEach((s) => {
                    this.scroll.formatAt(e, r.length, s, n[s]);
                }),
                this.update(new z().retain(e).insert(r, Kr(n)))
            );
        }
        isBlank() {
            if (this.scroll.children.length === 0) return !0;
            if (this.scroll.children.length > 1) return !1;
            const e = this.scroll.children.head;
            if (e?.statics.blotName !== Ne.blotName) return !1;
            const r = e;
            return r.children.length > 1 ? !1 : r.children.head instanceof ft;
        }
        removeFormat(e, r) {
            const n = this.getText(e, r),
                [s, i] = this.scroll.line(e + r);
            let o = 0,
                a = new z();
            s != null &&
                ((o = s.length() - i),
                (a = s.delta().slice(i, i + o - 1).insert(`
`)));
            const f = this.getContents(e, r + o).diff(
                    new z().insert(n).concat(a),
                ),
                u = new z().retain(e).concat(f);
            return this.applyDelta(u);
        }
        update(e) {
            let r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : [],
                n =
                    arguments.length > 2 && arguments[2] !== void 0
                        ? arguments[2]
                        : void 0;
            const s = this.delta;
            if (
                r.length === 1 &&
                r[0].type === "characterData" &&
                r[0].target.data.match(pw) &&
                this.scroll.find(r[0].target)
            ) {
                const i = this.scroll.find(r[0].target),
                    o = We(i),
                    a = i.offset(this.scroll),
                    l = r[0].oldValue.replace(tt.CONTENTS, ""),
                    f = new z().insert(l),
                    u = new z().insert(i.value()),
                    d = n && {
                        oldRange: O0(n.oldRange, -a),
                        newRange: O0(n.newRange, -a),
                    };
                ((e = new z()
                    .retain(a)
                    .concat(f.diff(u, d))
                    .reduce(
                        (g, v) =>
                            v.insert ? g.insert(v.insert, o) : g.push(v),
                        new z(),
                    )),
                    (this.delta = s.compose(e)));
            } else
                ((this.delta = this.getDelta()),
                    (!e || !Ja(s.compose(e), this.delta)) &&
                        (e = s.diff(this.delta, n)));
            return e;
        }
    }
    function Hr(t, e, r) {
        if (t.length === 0) {
            const [g] = To(r.pop());
            return e <= 0 ? `</li></${g}>` : `</li></${g}>${Hr([], e - 1, r)}`;
        }
        const [{ child: n, offset: s, length: i, indent: o, type: a }, ...l] =
                t,
            [f, u] = To(a);
        if (o > e)
            return (
                r.push(a),
                o === e + 1
                    ? `<${f}><li${u}>${Vn(n, s, i)}${Hr(l, o, r)}`
                    : `<${f}><li>${Hr(t, e + 1, r)}`
            );
        const d = r[r.length - 1];
        if (o === e && a === d)
            return `</li><li${u}>${Vn(n, s, i)}${Hr(l, o, r)}`;
        const [h] = To(r.pop());
        return `</li></${h}>${Hr(t, e - 1, r)}`;
    }
    function Vn(t, e, r) {
        let n =
            arguments.length > 3 && arguments[3] !== void 0 ? arguments[3] : !1;
        if ("html" in t && typeof t.html == "function") return t.html(e, r);
        if (t instanceof it)
            return yi(t.value().slice(e, e + r)).replaceAll(" ", "&nbsp;");
        if (t instanceof nt) {
            if (t.statics.blotName === "list-container") {
                const f = [];
                return (
                    t.children.forEachAt(e, r, (u, d, h) => {
                        const g =
                            "formats" in u && typeof u.formats == "function"
                                ? u.formats()
                                : {};
                        f.push({
                            child: u,
                            offset: d,
                            length: h,
                            indent: g.indent || 0,
                            type: g.list,
                        });
                    }),
                    Hr(f, -1, [])
                );
            }
            const s = [];
            if (
                (t.children.forEachAt(e, r, (f, u, d) => {
                    s.push(Vn(f, u, d));
                }),
                n || t.statics.blotName === "list")
            )
                return s.join("");
            const { outerHTML: i, innerHTML: o } = t.domNode,
                [a, l] = i.split(`>${o}<`);
            return a === "<table"
                ? `<table style="border: 1px solid #000;">${s.join("")}<${l}`
                : `${a}>${s.join("")}<${l}`;
        }
        return t.domNode instanceof Element ? t.domNode.outerHTML : "";
    }
    function mw(t, e) {
        return Object.keys(e).reduce((r, n) => {
            if (t[n] == null) return r;
            const s = e[n];
            return (
                s === t[n]
                    ? (r[n] = s)
                    : Array.isArray(s)
                      ? s.indexOf(t[n]) < 0
                          ? (r[n] = s.concat([t[n]]))
                          : (r[n] = s)
                      : (r[n] = [s, t[n]]),
                r
            );
        }, {});
    }
    function To(t) {
        const e = t === "ordered" ? "ol" : "ul";
        switch (t) {
            case "checked":
                return [e, ' data-list="checked"'];
            case "unchecked":
                return [e, ' data-list="unchecked"'];
            default:
                return [e, ""];
        }
    }
    function N0(t) {
        return t.reduce((e, r) => {
            if (typeof r.insert == "string") {
                const n = r.insert
                    .replace(
                        /\r\n/g,
                        `
`,
                    )
                    .replace(
                        /\r/g,
                        `
`,
                    );
                return e.insert(n, r.attributes);
            }
            return e.push(r);
        }, new z());
    }
    function O0(t, e) {
        let { index: r, length: n } = t;
        return new yr(r + e, n);
    }
    function bw(t) {
        const e = [];
        return (
            t.forEach((r) => {
                typeof r.insert == "string"
                    ? r.insert
                          .split(
                              `
`,
                          )
                          .forEach((s, i) => {
                              (i &&
                                  e.push({
                                      insert: `
`,
                                      attributes: r.attributes,
                                  }),
                                  s &&
                                      e.push({
                                          insert: s,
                                          attributes: r.attributes,
                                      }));
                          })
                    : e.push(r);
            }),
            e
        );
    }
    class wt {
        static DEFAULTS = {};
        constructor(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : {};
            ((this.quill = e), (this.options = r));
        }
    }
    const Is = "\uFEFF";
    class el extends He {
        constructor(e, r) {
            (super(e, r),
                (this.contentNode = document.createElement("span")),
                this.contentNode.setAttribute("contenteditable", "false"),
                Array.from(this.domNode.childNodes).forEach((n) => {
                    this.contentNode.appendChild(n);
                }),
                (this.leftGuard = document.createTextNode(Is)),
                (this.rightGuard = document.createTextNode(Is)),
                this.domNode.appendChild(this.leftGuard),
                this.domNode.appendChild(this.contentNode),
                this.domNode.appendChild(this.rightGuard));
        }
        index(e, r) {
            return e === this.leftGuard
                ? 0
                : e === this.rightGuard
                  ? 1
                  : super.index(e, r);
        }
        restore(e) {
            let r = null,
                n;
            const s = e.data.split(Is).join("");
            if (e === this.leftGuard)
                if (this.prev instanceof it) {
                    const i = this.prev.length();
                    (this.prev.insertAt(i, s),
                        (r = {
                            startNode: this.prev.domNode,
                            startOffset: i + s.length,
                        }));
                } else
                    ((n = document.createTextNode(s)),
                        this.parent.insertBefore(this.scroll.create(n), this),
                        (r = { startNode: n, startOffset: s.length }));
            else
                e === this.rightGuard &&
                    (this.next instanceof it
                        ? (this.next.insertAt(0, s),
                          (r = {
                              startNode: this.next.domNode,
                              startOffset: s.length,
                          }))
                        : ((n = document.createTextNode(s)),
                          this.parent.insertBefore(
                              this.scroll.create(n),
                              this.next,
                          ),
                          (r = { startNode: n, startOffset: s.length })));
            return ((e.data = Is), r);
        }
        update(e, r) {
            e.forEach((n) => {
                if (
                    n.type === "characterData" &&
                    (n.target === this.leftGuard ||
                        n.target === this.rightGuard)
                ) {
                    const s = this.restore(n.target);
                    s && (r.range = s);
                }
            });
        }
    }
    class yw {
        isComposing = !1;
        constructor(e, r) {
            ((this.scroll = e), (this.emitter = r), this.setupListeners());
        }
        setupListeners() {
            (this.scroll.domNode.addEventListener("compositionstart", (e) => {
                this.isComposing || this.handleCompositionStart(e);
            }),
                this.scroll.domNode.addEventListener("compositionend", (e) => {
                    this.isComposing &&
                        queueMicrotask(() => {
                            this.handleCompositionEnd(e);
                        });
                }));
        }
        handleCompositionStart(e) {
            const r =
                e.target instanceof Node
                    ? this.scroll.find(e.target, !0)
                    : null;
            r &&
                !(r instanceof el) &&
                (this.emitter.emit(H.events.COMPOSITION_BEFORE_START, e),
                this.scroll.batchStart(),
                this.emitter.emit(H.events.COMPOSITION_START, e),
                (this.isComposing = !0));
        }
        handleCompositionEnd(e) {
            (this.emitter.emit(H.events.COMPOSITION_BEFORE_END, e),
                this.scroll.batchEnd(),
                this.emitter.emit(H.events.COMPOSITION_END, e),
                (this.isComposing = !1));
        }
    }
    class ln {
        static DEFAULTS = { modules: {} };
        static themes = { default: ln };
        modules = {};
        constructor(e, r) {
            ((this.quill = e), (this.options = r));
        }
        init() {
            Object.keys(this.options.modules).forEach((e) => {
                this.modules[e] == null && this.addModule(e);
            });
        }
        addModule(e) {
            const r = this.quill.constructor.import(`modules/${e}`);
            return (
                (this.modules[e] = new r(
                    this.quill,
                    this.options.modules[e] || {},
                )),
                this.modules[e]
            );
        }
    }
    const vw = (t) => t.parentElement || t.getRootNode().host || null,
        ww = (t) => {
            const e = t.getBoundingClientRect(),
                r =
                    ("offsetWidth" in t && Math.abs(e.width) / t.offsetWidth) ||
                    1,
                n =
                    ("offsetHeight" in t &&
                        Math.abs(e.height) / t.offsetHeight) ||
                    1;
            return {
                top: e.top,
                right: e.left + t.clientWidth * r,
                bottom: e.top + t.clientHeight * n,
                left: e.left,
            };
        },
        Ps = (t) => {
            const e = parseInt(t, 10);
            return Number.isNaN(e) ? 0 : e;
        },
        C0 = (t, e, r, n, s, i) =>
            t < r && e > n
                ? 0
                : t < r
                  ? -(r - t + s)
                  : e > n
                    ? e - t > n - r
                        ? t + s - r
                        : e - n + i
                    : 0,
        Ew = (t, e) => {
            const r = t.ownerDocument;
            let n = e,
                s = t;
            for (; s; ) {
                const i = s === r.body,
                    o = i
                        ? {
                              top: 0,
                              right:
                                  window.visualViewport?.width ??
                                  r.documentElement.clientWidth,
                              bottom:
                                  window.visualViewport?.height ??
                                  r.documentElement.clientHeight,
                              left: 0,
                          }
                        : ww(s),
                    a = getComputedStyle(s),
                    l = C0(
                        n.left,
                        n.right,
                        o.left,
                        o.right,
                        Ps(a.scrollPaddingLeft),
                        Ps(a.scrollPaddingRight),
                    ),
                    f = C0(
                        n.top,
                        n.bottom,
                        o.top,
                        o.bottom,
                        Ps(a.scrollPaddingTop),
                        Ps(a.scrollPaddingBottom),
                    );
                if (l || f)
                    if (i) r.defaultView?.scrollBy(l, f);
                    else {
                        const { scrollLeft: u, scrollTop: d } = s;
                        (f && (s.scrollTop += f), l && (s.scrollLeft += l));
                        const h = s.scrollLeft - u,
                            g = s.scrollTop - d;
                        n = {
                            left: n.left - h,
                            top: n.top - g,
                            right: n.right - h,
                            bottom: n.bottom - g,
                        };
                    }
                s = i || a.position === "fixed" ? null : vw(s);
            }
        },
        _w = 100,
        xw = ["block", "break", "cursor", "inline", "scroll", "text"],
        Aw = (t, e, r) => {
            const n = new Yr();
            return (
                xw.forEach((s) => {
                    const i = e.query(s);
                    i && n.register(i);
                }),
                t.forEach((s) => {
                    let i = e.query(s);
                    i ||
                        r.error(
                            `Cannot register "${s}" specified in "formats" config. Are you sure it was registered?`,
                        );
                    let o = 0;
                    for (; i; )
                        if (
                            (n.register(i),
                            (i =
                                "blotName" in i
                                    ? (i.requiredContainer ?? null)
                                    : null),
                            (o += 1),
                            o > _w)
                        ) {
                            r.error(
                                `Cycle detected in registering blot requiredContainer: "${s}"`,
                            );
                            break;
                        }
                }),
                n
            );
        },
        Vr = qt("quill"),
        Ms = new Yr();
    nt.uiClass = "ql-ui";
    class L {
        static DEFAULTS = {
            bounds: null,
            modules: { clipboard: !0, keyboard: !0, history: !0, uploader: !0 },
            placeholder: "",
            readOnly: !1,
            registry: Ms,
            theme: "default",
        };
        static events = H.events;
        static sources = H.sources;
        static version = "2.0.3";
        static imports = {
            delta: z,
            parchment: nw,
            "core/module": wt,
            "core/theme": ln,
        };
        static debug(e) {
            (e === !0 && (e = "log"), qt.level(e));
        }
        static find(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : !1;
            return fa.get(e) || Ms.find(e, r);
        }
        static import(e) {
            return (
                this.imports[e] == null &&
                    Vr.error(
                        `Cannot import ${e}. Are you sure it was registered?`,
                    ),
                this.imports[e]
            );
        }
        static register() {
            if (
                typeof (arguments.length <= 0 ? void 0 : arguments[0]) !=
                "string"
            ) {
                const e = arguments.length <= 0 ? void 0 : arguments[0],
                    r = !!(!(arguments.length <= 1) && arguments[1]),
                    n = "attrName" in e ? e.attrName : e.blotName;
                typeof n == "string"
                    ? this.register(`formats/${n}`, e, r)
                    : Object.keys(e).forEach((s) => {
                          this.register(s, e[s], r);
                      });
            } else {
                const e = arguments.length <= 0 ? void 0 : arguments[0],
                    r = arguments.length <= 1 ? void 0 : arguments[1],
                    n = !!(!(arguments.length <= 2) && arguments[2]);
                (this.imports[e] != null &&
                    !n &&
                    Vr.warn(`Overwriting ${e} with`, r),
                    (this.imports[e] = r),
                    (e.startsWith("blots/") || e.startsWith("formats/")) &&
                        r &&
                        typeof r != "boolean" &&
                        r.blotName !== "abstract" &&
                        Ms.register(r),
                    typeof r.register == "function" && r.register(Ms));
            }
        }
        constructor(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : {};
            if (
                ((this.options = Sw(e, r)),
                (this.container = this.options.container),
                this.container == null)
            ) {
                Vr.error("Invalid Quill container", e);
                return;
            }
            this.options.debug && L.debug(this.options.debug);
            const n = this.container.innerHTML.trim();
            (this.container.classList.add("ql-container"),
                (this.container.innerHTML = ""),
                fa.set(this.container, this),
                (this.root = this.addContainer("ql-editor")),
                this.root.classList.add("ql-blank"),
                (this.emitter = new H()));
            const s = Qa.blotName,
                i = this.options.registry.query(s);
            if (!i || !("blotName" in i))
                throw new Error(`Cannot initialize Quill without "${s}" blot`);
            if (
                ((this.scroll = new i(this.options.registry, this.root, {
                    emitter: this.emitter,
                })),
                (this.editor = new gw(this.scroll)),
                (this.selection = new dw(this.scroll, this.emitter)),
                (this.composition = new yw(this.scroll, this.emitter)),
                (this.theme = new this.options.theme(this, this.options)),
                (this.keyboard = this.theme.addModule("keyboard")),
                (this.clipboard = this.theme.addModule("clipboard")),
                (this.history = this.theme.addModule("history")),
                (this.uploader = this.theme.addModule("uploader")),
                this.theme.addModule("input"),
                this.theme.addModule("uiNode"),
                this.theme.init(),
                this.emitter.on(H.events.EDITOR_CHANGE, (o) => {
                    o === H.events.TEXT_CHANGE &&
                        this.root.classList.toggle(
                            "ql-blank",
                            this.editor.isBlank(),
                        );
                }),
                this.emitter.on(H.events.SCROLL_UPDATE, (o, a) => {
                    const l = this.selection.lastRange,
                        [f] = this.selection.getRange(),
                        u = l && f ? { oldRange: l, newRange: f } : void 0;
                    Qe.call(this, () => this.editor.update(null, a, u), o);
                }),
                this.emitter.on(H.events.SCROLL_EMBED_UPDATE, (o, a) => {
                    const l = this.selection.lastRange,
                        [f] = this.selection.getRange(),
                        u = l && f ? { oldRange: l, newRange: f } : void 0;
                    Qe.call(
                        this,
                        () => {
                            const d = new z()
                                .retain(o.offset(this))
                                .retain({ [o.statics.blotName]: a });
                            return this.editor.update(d, [], u);
                        },
                        L.sources.USER,
                    );
                }),
                n)
            ) {
                const o = this.clipboard.convert({
                    html: `${n}<p><br></p>`,
                    text: `
`,
                });
                this.setContents(o);
            }
            (this.history.clear(),
                this.options.placeholder &&
                    this.root.setAttribute(
                        "data-placeholder",
                        this.options.placeholder,
                    ),
                this.options.readOnly && this.disable(),
                (this.allowReadOnlyEdits = !1));
        }
        addContainer(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : null;
            if (typeof e == "string") {
                const n = e;
                ((e = document.createElement("div")), e.classList.add(n));
            }
            return (this.container.insertBefore(e, r), e);
        }
        blur() {
            this.selection.setRange(null);
        }
        deleteText(e, r, n) {
            return (
                ([e, r, , n] = Tt(e, r, n)),
                Qe.call(this, () => this.editor.deleteText(e, r), n, e, -1 * r)
            );
        }
        disable() {
            this.enable(!1);
        }
        editReadOnly(e) {
            this.allowReadOnlyEdits = !0;
            const r = e();
            return ((this.allowReadOnlyEdits = !1), r);
        }
        enable() {
            let e =
                arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : !0;
            (this.scroll.enable(e),
                this.container.classList.toggle("ql-disabled", !e));
        }
        focus() {
            let e =
                arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : {};
            (this.selection.focus(),
                e.preventScroll || this.scrollSelectionIntoView());
        }
        format(e, r) {
            let n =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : H.sources.API;
            return Qe.call(
                this,
                () => {
                    const s = this.getSelection(!0);
                    let i = new z();
                    if (s == null) return i;
                    if (this.scroll.query(e, K.BLOCK))
                        i = this.editor.formatLine(s.index, s.length, {
                            [e]: r,
                        });
                    else {
                        if (s.length === 0)
                            return (this.selection.format(e, r), i);
                        i = this.editor.formatText(s.index, s.length, {
                            [e]: r,
                        });
                    }
                    return (this.setSelection(s, H.sources.SILENT), i);
                },
                n,
            );
        }
        formatLine(e, r, n, s, i) {
            let o;
            return (
                ([e, r, o, i] = Tt(e, r, n, s, i)),
                Qe.call(this, () => this.editor.formatLine(e, r, o), i, e, 0)
            );
        }
        formatText(e, r, n, s, i) {
            let o;
            return (
                ([e, r, o, i] = Tt(e, r, n, s, i)),
                Qe.call(this, () => this.editor.formatText(e, r, o), i, e, 0)
            );
        }
        getBounds(e) {
            let r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : 0,
                n = null;
            if (
                (typeof e == "number"
                    ? (n = this.selection.getBounds(e, r))
                    : (n = this.selection.getBounds(e.index, e.length)),
                !n)
            )
                return null;
            const s = this.container.getBoundingClientRect();
            return {
                bottom: n.bottom - s.top,
                height: n.height,
                left: n.left - s.left,
                right: n.right - s.left,
                top: n.top - s.top,
                width: n.width,
            };
        }
        getContents() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : 0,
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : this.getLength() - e;
            return (([e, r] = Tt(e, r)), this.editor.getContents(e, r));
        }
        getFormat() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : this.getSelection(!0),
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : 0;
            return typeof e == "number"
                ? this.editor.getFormat(e, r)
                : this.editor.getFormat(e.index, e.length);
        }
        getIndex(e) {
            return e.offset(this.scroll);
        }
        getLength() {
            return this.scroll.length();
        }
        getLeaf(e) {
            return this.scroll.leaf(e);
        }
        getLine(e) {
            return this.scroll.line(e);
        }
        getLines() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : 0,
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : Number.MAX_VALUE;
            return typeof e != "number"
                ? this.scroll.lines(e.index, e.length)
                : this.scroll.lines(e, r);
        }
        getModule(e) {
            return this.theme.modules[e];
        }
        getSelection() {
            return (
                arguments.length > 0 &&
                    arguments[0] !== void 0 &&
                    arguments[0] &&
                    this.focus(),
                this.update(),
                this.selection.getRange()[0]
            );
        }
        getSemanticHTML() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : 0,
                r = arguments.length > 1 ? arguments[1] : void 0;
            return (
                typeof e == "number" && (r = r ?? this.getLength() - e),
                ([e, r] = Tt(e, r)),
                this.editor.getHTML(e, r)
            );
        }
        getText() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : 0,
                r = arguments.length > 1 ? arguments[1] : void 0;
            return (
                typeof e == "number" && (r = r ?? this.getLength() - e),
                ([e, r] = Tt(e, r)),
                this.editor.getText(e, r)
            );
        }
        hasFocus() {
            return this.selection.hasFocus();
        }
        insertEmbed(e, r, n) {
            let s =
                arguments.length > 3 && arguments[3] !== void 0
                    ? arguments[3]
                    : L.sources.API;
            return Qe.call(this, () => this.editor.insertEmbed(e, r, n), s, e);
        }
        insertText(e, r, n, s, i) {
            let o;
            return (
                ([e, , o, i] = Tt(e, 0, n, s, i)),
                Qe.call(
                    this,
                    () => this.editor.insertText(e, r, o),
                    i,
                    e,
                    r.length,
                )
            );
        }
        isEnabled() {
            return this.scroll.isEnabled();
        }
        off() {
            return this.emitter.off(...arguments);
        }
        on() {
            return this.emitter.on(...arguments);
        }
        once() {
            return this.emitter.once(...arguments);
        }
        removeFormat(e, r, n) {
            return (
                ([e, r, , n] = Tt(e, r, n)),
                Qe.call(this, () => this.editor.removeFormat(e, r), n, e)
            );
        }
        scrollRectIntoView(e) {
            Ew(this.root, e);
        }
        scrollIntoView() {
            (console.warn(
                "Quill#scrollIntoView() has been deprecated and will be removed in the near future. Please use Quill#scrollSelectionIntoView() instead.",
            ),
                this.scrollSelectionIntoView());
        }
        scrollSelectionIntoView() {
            const e = this.selection.lastRange,
                r = e && this.selection.getBounds(e.index, e.length);
            r && this.scrollRectIntoView(r);
        }
        setContents(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : H.sources.API;
            return Qe.call(
                this,
                () => {
                    e = new z(e);
                    const n = this.getLength(),
                        s = this.editor.deleteText(0, n),
                        i = this.editor.insertContents(0, e),
                        o = this.editor.deleteText(this.getLength() - 1, 1);
                    return s.compose(i).compose(o);
                },
                r,
            );
        }
        setSelection(e, r, n) {
            e == null
                ? this.selection.setRange(null, r || L.sources.API)
                : (([e, r, , n] = Tt(e, r, n)),
                  this.selection.setRange(new yr(Math.max(0, e), r), n),
                  n !== H.sources.SILENT && this.scrollSelectionIntoView());
        }
        setText(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : H.sources.API;
            const n = new z().insert(e);
            return this.setContents(n, r);
        }
        update() {
            let e =
                arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : H.sources.USER;
            const r = this.scroll.update(e);
            return (this.selection.update(e), r);
        }
        updateContents(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : H.sources.API;
            return Qe.call(
                this,
                () => ((e = new z(e)), this.editor.applyDelta(e)),
                r,
                !0,
            );
        }
    }
    function L0(t) {
        return typeof t == "string" ? document.querySelector(t) : t;
    }
    function No(t) {
        return Object.entries(t ?? {}).reduce((e, r) => {
            let [n, s] = r;
            return { ...e, [n]: s === !0 ? {} : s };
        }, {});
    }
    function R0(t) {
        return Object.fromEntries(
            Object.entries(t).filter((e) => e[1] !== void 0),
        );
    }
    function Sw(t, e) {
        const r = L0(t);
        if (!r) throw new Error("Invalid Quill container");
        const s =
            !e.theme || e.theme === L.DEFAULTS.theme
                ? ln
                : L.import(`themes/${e.theme}`);
        if (!s)
            throw new Error(`Invalid theme ${e.theme}. Did you register it?`);
        const { modules: i, ...o } = L.DEFAULTS,
            { modules: a, ...l } = s.DEFAULTS;
        let f = No(e.modules);
        f != null &&
            f.toolbar &&
            f.toolbar.constructor !== Object &&
            (f = { ...f, toolbar: { container: f.toolbar } });
        const u = jt({}, No(i), No(a), f),
            d = { ...o, ...R0(l), ...R0(e) };
        let h = e.registry;
        return (
            h
                ? e.formats &&
                  Vr.warn(
                      'Ignoring "formats" option because "registry" is specified',
                  )
                : (h = e.formats ? Aw(e.formats, d.registry, Vr) : d.registry),
            {
                ...d,
                registry: h,
                container: r,
                theme: s,
                modules: Object.entries(u).reduce((g, v) => {
                    let [b, _] = v;
                    if (!_) return g;
                    const y = L.import(`modules/${b}`);
                    return y == null
                        ? (Vr.error(
                              `Cannot load ${b} module. Are you sure you registered it?`,
                          ),
                          g)
                        : { ...g, [b]: jt({}, y.DEFAULTS || {}, _) };
                }, {}),
                bounds: L0(d.bounds),
            }
        );
    }
    function Qe(t, e, r, n) {
        if (
            !this.isEnabled() &&
            e === H.sources.USER &&
            !this.allowReadOnlyEdits
        )
            return new z();
        let s = r == null ? null : this.getSelection();
        const i = this.editor.delta,
            o = t();
        if (
            (s != null &&
                (r === !0 && (r = s.index),
                n == null ? (s = q0(s, o, e)) : n !== 0 && (s = q0(s, r, n, e)),
                this.setSelection(s, H.sources.SILENT)),
            o.length() > 0)
        ) {
            const a = [H.events.TEXT_CHANGE, o, i, e];
            (this.emitter.emit(H.events.EDITOR_CHANGE, ...a),
                e !== H.sources.SILENT && this.emitter.emit(...a));
        }
        return o;
    }
    function Tt(t, e, r, n, s) {
        let i = {};
        return (
            typeof t.index == "number" && typeof t.length == "number"
                ? typeof e != "number"
                    ? ((s = n), (n = r), (r = e), (e = t.length), (t = t.index))
                    : ((e = t.length), (t = t.index))
                : typeof e != "number" && ((s = n), (n = r), (r = e), (e = 0)),
            typeof r == "object"
                ? ((i = r), (s = n))
                : typeof r == "string" && (n != null ? (i[r] = n) : (s = r)),
            (s = s || H.sources.API),
            [t, e, i, s]
        );
    }
    function q0(t, e, r, n) {
        const s = typeof r == "number" ? r : 0;
        if (t == null) return null;
        let i, o;
        return (
            e && typeof e.transformPosition == "function"
                ? ([i, o] = [t.index, t.index + t.length].map((a) =>
                      e.transformPosition(a, n !== H.sources.USER),
                  ))
                : ([i, o] = [t.index, t.index + t.length].map((a) =>
                      a < e || (a === e && n === H.sources.USER)
                          ? a
                          : s >= 0
                            ? a + s
                            : Math.max(e, a + s),
                  )),
            new yr(i, o - i)
        );
    }
    class Ar extends bi {}
    function k0(t) {
        return t instanceof Ne || t instanceof Ge;
    }
    function I0(t) {
        return typeof t.updateContent == "function";
    }
    class Tw extends Qa {
        static blotName = "scroll";
        static className = "ql-editor";
        static tagName = "DIV";
        static defaultChild = Ne;
        static allowedChildren = [Ne, Ge, Ar];
        constructor(e, r, n) {
            let { emitter: s } = n;
            (super(e, r),
                (this.emitter = s),
                (this.batch = !1),
                this.optimize(),
                this.enable(),
                this.domNode.addEventListener("dragstart", (i) =>
                    this.handleDragStart(i),
                ));
        }
        batchStart() {
            Array.isArray(this.batch) || (this.batch = []);
        }
        batchEnd() {
            if (!this.batch) return;
            const e = this.batch;
            ((this.batch = !1), this.update(e));
        }
        emitMount(e) {
            this.emitter.emit(H.events.SCROLL_BLOT_MOUNT, e);
        }
        emitUnmount(e) {
            this.emitter.emit(H.events.SCROLL_BLOT_UNMOUNT, e);
        }
        emitEmbedUpdate(e, r) {
            this.emitter.emit(H.events.SCROLL_EMBED_UPDATE, e, r);
        }
        deleteAt(e, r) {
            const [n, s] = this.line(e),
                [i] = this.line(e + r);
            if ((super.deleteAt(e, r), i != null && n !== i && s > 0)) {
                if (n instanceof Ge || i instanceof Ge) {
                    this.optimize();
                    return;
                }
                const o =
                    i.children.head instanceof ft ? null : i.children.head;
                (n.moveChildren(i, o), n.remove());
            }
            this.optimize();
        }
        enable() {
            let e =
                arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : !0;
            this.domNode.setAttribute("contenteditable", e ? "true" : "false");
        }
        formatAt(e, r, n, s) {
            (super.formatAt(e, r, n, s), this.optimize());
        }
        insertAt(e, r, n) {
            if (e >= this.length())
                if (n == null || this.scroll.query(r, K.BLOCK) == null) {
                    const s = this.scroll.create(
                        this.statics.defaultChild.blotName,
                    );
                    (this.appendChild(s),
                        n == null &&
                        r.endsWith(`
`)
                            ? s.insertAt(0, r.slice(0, -1), n)
                            : s.insertAt(0, r, n));
                } else {
                    const s = this.scroll.create(r, n);
                    this.appendChild(s);
                }
            else super.insertAt(e, r, n);
            this.optimize();
        }
        insertBefore(e, r) {
            if (e.statics.scope === K.INLINE_BLOT) {
                const n = this.scroll.create(
                    this.statics.defaultChild.blotName,
                );
                (n.appendChild(e), super.insertBefore(n, r));
            } else super.insertBefore(e, r);
        }
        insertContents(e, r) {
            const n = this.deltaToRenderBlocks(
                    r.concat(
                        new z().insert(`
`),
                    ),
                ),
                s = n.pop();
            if (s == null) return;
            this.batchStart();
            const i = n.shift();
            if (i) {
                const l =
                        i.type === "block" &&
                        (i.delta.length() === 0 ||
                            (!this.descendant(Ge, e)[0] && e < this.length())),
                    f =
                        i.type === "block"
                            ? i.delta
                            : new z().insert({ [i.key]: i.value });
                Oo(this, e, f);
                const u = i.type === "block" ? 1 : 0,
                    d = e + f.length() + u;
                l &&
                    this.insertAt(
                        d - 1,
                        `
`,
                    );
                const h = We(this.line(e)[0]),
                    g = Ze.AttributeMap.diff(h, i.attributes) || {};
                (Object.keys(g).forEach((v) => {
                    this.formatAt(d - 1, 1, v, g[v]);
                }),
                    (e = d));
            }
            let [o, a] = this.children.find(e);
            if (
                (n.length &&
                    (o && ((o = o.split(a)), (a = 0)),
                    n.forEach((l) => {
                        if (l.type === "block") {
                            const f = this.createBlock(
                                l.attributes,
                                o || void 0,
                            );
                            Oo(f, 0, l.delta);
                        } else {
                            const f = this.create(l.key, l.value);
                            (this.insertBefore(f, o || void 0),
                                Object.keys(l.attributes).forEach((u) => {
                                    f.format(u, l.attributes[u]);
                                }));
                        }
                    })),
                s.type === "block" && s.delta.length())
            ) {
                const l = o ? o.offset(o.scroll) + a : this.length();
                Oo(this, l, s.delta);
            }
            (this.batchEnd(), this.optimize());
        }
        isEnabled() {
            return this.domNode.getAttribute("contenteditable") === "true";
        }
        leaf(e) {
            const r = this.path(e).pop();
            if (!r) return [null, -1];
            const [n, s] = r;
            return n instanceof qe ? [n, s] : [null, -1];
        }
        line(e) {
            return e === this.length()
                ? this.line(e - 1)
                : this.descendant(k0, e);
        }
        lines() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : 0,
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : Number.MAX_VALUE;
            const n = (s, i, o) => {
                let a = [],
                    l = o;
                return (
                    s.children.forEachAt(i, o, (f, u, d) => {
                        (k0(f)
                            ? a.push(f)
                            : f instanceof bi && (a = a.concat(n(f, u, l))),
                            (l -= d));
                    }),
                    a
                );
            };
            return n(this, e, r);
        }
        optimize() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : [],
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : {};
            this.batch ||
                (super.optimize(e, r),
                e.length > 0 &&
                    this.emitter.emit(H.events.SCROLL_OPTIMIZE, e, r));
        }
        path(e) {
            return super.path(e).slice(1);
        }
        remove() {}
        update(e) {
            if (this.batch) {
                Array.isArray(e) && (this.batch = this.batch.concat(e));
                return;
            }
            let r = H.sources.USER;
            (typeof e == "string" && (r = e),
                Array.isArray(e) || (e = this.observer.takeRecords()),
                (e = e.filter((n) => {
                    let { target: s } = n;
                    const i = this.find(s, !0);
                    return i && !I0(i);
                })),
                e.length > 0 &&
                    this.emitter.emit(H.events.SCROLL_BEFORE_UPDATE, r, e),
                super.update(e.concat([])),
                e.length > 0 &&
                    this.emitter.emit(H.events.SCROLL_UPDATE, r, e));
        }
        updateEmbedAt(e, r, n) {
            const [s] = this.descendant((i) => i instanceof Ge, e);
            s && s.statics.blotName === r && I0(s) && s.updateContent(n);
        }
        handleDragStart(e) {
            e.preventDefault();
        }
        deltaToRenderBlocks(e) {
            const r = [];
            let n = new z();
            return (
                e.forEach((s) => {
                    const i = s?.insert;
                    if (i)
                        if (typeof i == "string") {
                            const o = i.split(`
`);
                            o.slice(0, -1).forEach((l) => {
                                (n.insert(l, s.attributes),
                                    r.push({
                                        type: "block",
                                        delta: n,
                                        attributes: s.attributes ?? {},
                                    }),
                                    (n = new z()));
                            });
                            const a = o[o.length - 1];
                            a && n.insert(a, s.attributes);
                        } else {
                            const o = Object.keys(i)[0];
                            if (!o) return;
                            this.query(o, K.INLINE)
                                ? n.push(s)
                                : (n.length() &&
                                      r.push({
                                          type: "block",
                                          delta: n,
                                          attributes: {},
                                      }),
                                  (n = new z()),
                                  r.push({
                                      type: "blockEmbed",
                                      key: o,
                                      value: i[o],
                                      attributes: s.attributes ?? {},
                                  }));
                        }
                }),
                n.length() &&
                    r.push({ type: "block", delta: n, attributes: {} }),
                r
            );
        }
        createBlock(e, r) {
            let n;
            const s = {};
            Object.entries(e).forEach((a) => {
                let [l, f] = a;
                this.query(l, K.BLOCK & K.BLOT) != null ? (n = l) : (s[l] = f);
            });
            const i = this.create(
                n || this.statics.defaultChild.blotName,
                n ? e[n] : void 0,
            );
            this.insertBefore(i, r || void 0);
            const o = i.length();
            return (
                Object.entries(s).forEach((a) => {
                    let [l, f] = a;
                    i.formatAt(0, o, l, f);
                }),
                i
            );
        }
    }
    function Oo(t, e, r) {
        r.reduce((n, s) => {
            const i = Ze.Op.length(s);
            let o = s.attributes || {};
            if (s.insert != null) {
                if (typeof s.insert == "string") {
                    const a = s.insert;
                    t.insertAt(n, a);
                    const [l] = t.descendant(qe, n),
                        f = We(l);
                    o = Ze.AttributeMap.diff(f, o) || {};
                } else if (typeof s.insert == "object") {
                    const a = Object.keys(s.insert)[0];
                    if (a == null) return n;
                    if (
                        (t.insertAt(n, a, s.insert[a]),
                        t.scroll.query(a, K.INLINE) != null)
                    ) {
                        const [f] = t.descendant(qe, n),
                            u = We(f);
                        o = Ze.AttributeMap.diff(u, o) || {};
                    }
                }
            }
            return (
                Object.keys(o).forEach((a) => {
                    t.formatAt(n, i, a, o[a]);
                }),
                n + i
            );
        }, e);
    }
    const tl = { scope: K.BLOCK, whitelist: ["right", "center", "justify"] },
        Nw = new yt("align", "align", tl),
        yu = new lt("align", "ql-align", tl),
        vu = new Vt("align", "text-align", tl);
    class wu extends Vt {
        value(e) {
            let r = super.value(e);
            return r.startsWith("rgb(")
                ? ((r = r.replace(/^[^\d]+/, "").replace(/[^\d]+$/, "")),
                  `#${r
                      .split(",")
                      .map((s) => `00${parseInt(s, 10).toString(16)}`.slice(-2))
                      .join("")}`)
                : r;
        }
    }
    const Ow = new lt("color", "ql-color", { scope: K.INLINE }),
        rl = new wu("color", "color", { scope: K.INLINE }),
        Cw = new lt("background", "ql-bg", { scope: K.INLINE }),
        nl = new wu("background", "background-color", { scope: K.INLINE });
    class Sr extends Ar {
        static create(e) {
            const r = super.create(e);
            return (r.setAttribute("spellcheck", "false"), r);
        }
        code(e, r) {
            return this.children
                .map((n) => (n.length() <= 1 ? "" : n.domNode.innerText))
                .join(
                    `
`,
                )
                .slice(e, e + r);
        }
        html(e, r) {
            return `<pre>
${yi(this.code(e, r))}
</pre>`;
        }
    }
    class Be extends Ne {
        static TAB = "  ";
        static register() {
            L.register(Sr);
        }
    }
    class sl extends Pe {}
    sl.blotName = "code";
    sl.tagName = "CODE";
    Be.blotName = "code-block";
    Be.className = "ql-code-block";
    Be.tagName = "DIV";
    Sr.blotName = "code-block-container";
    Sr.className = "ql-code-block-container";
    Sr.tagName = "DIV";
    Sr.allowedChildren = [Be];
    Be.allowedChildren = [it, ft, tt];
    Be.requiredContainer = Sr;
    const il = { scope: K.BLOCK, whitelist: ["rtl"] },
        Eu = new yt("direction", "dir", il),
        _u = new lt("direction", "ql-direction", il),
        xu = new Vt("direction", "direction", il),
        Au = { scope: K.INLINE, whitelist: ["serif", "monospace"] },
        Su = new lt("font", "ql-font", Au);
    class Lw extends Vt {
        value(e) {
            return super.value(e).replace(/["']/g, "");
        }
    }
    const Tu = new Lw("font", "font-family", Au),
        Nu = new lt("size", "ql-size", {
            scope: K.INLINE,
            whitelist: ["small", "large", "huge"],
        }),
        Ou = new Vt("size", "font-size", {
            scope: K.INLINE,
            whitelist: ["10px", "18px", "32px"],
        }),
        Rw = qt("quill:keyboard"),
        qw = /Mac/i.test(navigator.platform) ? "metaKey" : "ctrlKey";
    class vi extends wt {
        static match(e, r) {
            return ["altKey", "ctrlKey", "metaKey", "shiftKey"].some(
                (n) => !!r[n] !== e[n] && r[n] !== null,
            )
                ? !1
                : r.key === e.key || r.key === e.which;
        }
        constructor(e, r) {
            (super(e, r),
                (this.bindings = {}),
                Object.keys(this.options.bindings).forEach((n) => {
                    this.options.bindings[n] &&
                        this.addBinding(this.options.bindings[n]);
                }),
                this.addBinding(
                    { key: "Enter", shiftKey: null },
                    this.handleEnter,
                ),
                this.addBinding(
                    {
                        key: "Enter",
                        metaKey: null,
                        ctrlKey: null,
                        altKey: null,
                    },
                    () => {},
                ),
                /Firefox/i.test(navigator.userAgent)
                    ? (this.addBinding(
                          { key: "Backspace" },
                          { collapsed: !0 },
                          this.handleBackspace,
                      ),
                      this.addBinding(
                          { key: "Delete" },
                          { collapsed: !0 },
                          this.handleDelete,
                      ))
                    : (this.addBinding(
                          { key: "Backspace" },
                          { collapsed: !0, prefix: /^.?$/ },
                          this.handleBackspace,
                      ),
                      this.addBinding(
                          { key: "Delete" },
                          { collapsed: !0, suffix: /^.?$/ },
                          this.handleDelete,
                      )),
                this.addBinding(
                    { key: "Backspace" },
                    { collapsed: !1 },
                    this.handleDeleteRange,
                ),
                this.addBinding(
                    { key: "Delete" },
                    { collapsed: !1 },
                    this.handleDeleteRange,
                ),
                this.addBinding(
                    {
                        key: "Backspace",
                        altKey: null,
                        ctrlKey: null,
                        metaKey: null,
                        shiftKey: null,
                    },
                    { collapsed: !0, offset: 0 },
                    this.handleBackspace,
                ),
                this.listen());
        }
        addBinding(e) {
            let r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : {},
                n =
                    arguments.length > 2 && arguments[2] !== void 0
                        ? arguments[2]
                        : {};
            const s = Iw(e);
            if (s == null) {
                Rw.warn("Attempted to add invalid keyboard binding", s);
                return;
            }
            (typeof r == "function" && (r = { handler: r }),
                typeof n == "function" && (n = { handler: n }),
                (Array.isArray(s.key) ? s.key : [s.key]).forEach((o) => {
                    const a = { ...s, key: o, ...r, ...n };
                    ((this.bindings[a.key] = this.bindings[a.key] || []),
                        this.bindings[a.key].push(a));
                }));
        }
        listen() {
            this.quill.root.addEventListener("keydown", (e) => {
                if (
                    e.defaultPrevented ||
                    e.isComposing ||
                    (e.keyCode === 229 &&
                        (e.key === "Enter" || e.key === "Backspace"))
                )
                    return;
                const s = (this.bindings[e.key] || [])
                    .concat(this.bindings[e.which] || [])
                    .filter((y) => vi.match(e, y));
                if (s.length === 0) return;
                const i = L.find(e.target, !0);
                if (i && i.scroll !== this.quill.scroll) return;
                const o = this.quill.getSelection();
                if (o == null || !this.quill.hasFocus()) return;
                const [a, l] = this.quill.getLine(o.index),
                    [f, u] = this.quill.getLeaf(o.index),
                    [d, h] =
                        o.length === 0
                            ? [f, u]
                            : this.quill.getLeaf(o.index + o.length),
                    g = f instanceof ei ? f.value().slice(0, u) : "",
                    v = d instanceof ei ? d.value().slice(h) : "",
                    b = {
                        collapsed: o.length === 0,
                        empty: o.length === 0 && a.length() <= 1,
                        format: this.quill.getFormat(o),
                        line: a,
                        offset: l,
                        prefix: g,
                        suffix: v,
                        event: e,
                    };
                s.some((y) => {
                    if (
                        (y.collapsed != null && y.collapsed !== b.collapsed) ||
                        (y.empty != null && y.empty !== b.empty) ||
                        (y.offset != null && y.offset !== b.offset)
                    )
                        return !1;
                    if (Array.isArray(y.format)) {
                        if (y.format.every((S) => b.format[S] == null))
                            return !1;
                    } else if (
                        typeof y.format == "object" &&
                        !Object.keys(y.format).every((S) =>
                            y.format[S] === !0
                                ? b.format[S] != null
                                : y.format[S] === !1
                                  ? b.format[S] == null
                                  : Ja(y.format[S], b.format[S]),
                        )
                    )
                        return !1;
                    return (y.prefix != null && !y.prefix.test(b.prefix)) ||
                        (y.suffix != null && !y.suffix.test(b.suffix))
                        ? !1
                        : y.handler.call(this, o, b, y) !== !0;
                }) && e.preventDefault();
            });
        }
        handleBackspace(e, r) {
            const n = /[\uD800-\uDBFF][\uDC00-\uDFFF]$/.test(r.prefix) ? 2 : 1;
            if (e.index === 0 || this.quill.getLength() <= 1) return;
            let s = {};
            const [i] = this.quill.getLine(e.index);
            let o = new z().retain(e.index - n).delete(n);
            if (r.offset === 0) {
                const [a] = this.quill.getLine(e.index - 1);
                if (a && !(a.statics.blotName === "block" && a.length() <= 1)) {
                    const f = i.formats(),
                        u = this.quill.getFormat(e.index - 1, 1);
                    if (
                        ((s = Ze.AttributeMap.diff(f, u) || {}),
                        Object.keys(s).length > 0)
                    ) {
                        const d = new z()
                            .retain(e.index + i.length() - 2)
                            .retain(1, s);
                        o = o.compose(d);
                    }
                }
            }
            (this.quill.updateContents(o, L.sources.USER), this.quill.focus());
        }
        handleDelete(e, r) {
            const n = /^[\uD800-\uDBFF][\uDC00-\uDFFF]/.test(r.suffix) ? 2 : 1;
            if (e.index >= this.quill.getLength() - n) return;
            let s = {};
            const [i] = this.quill.getLine(e.index);
            let o = new z().retain(e.index).delete(n);
            if (r.offset >= i.length() - 1) {
                const [a] = this.quill.getLine(e.index + 1);
                if (a) {
                    const l = i.formats(),
                        f = this.quill.getFormat(e.index, 1);
                    ((s = Ze.AttributeMap.diff(l, f) || {}),
                        Object.keys(s).length > 0 &&
                            (o = o.retain(a.length() - 1).retain(1, s)));
                }
            }
            (this.quill.updateContents(o, L.sources.USER), this.quill.focus());
        }
        handleDeleteRange(e) {
            (ol({ range: e, quill: this.quill }), this.quill.focus());
        }
        handleEnter(e, r) {
            const n = Object.keys(r.format).reduce(
                    (i, o) => (
                        this.quill.scroll.query(o, K.BLOCK) &&
                            !Array.isArray(r.format[o]) &&
                            (i[o] = r.format[o]),
                        i
                    ),
                    {},
                ),
                s = new z()
                    .retain(e.index)
                    .delete(e.length)
                    .insert(
                        `
`,
                        n,
                    );
            (this.quill.updateContents(s, L.sources.USER),
                this.quill.setSelection(e.index + 1, L.sources.SILENT),
                this.quill.focus());
        }
    }
    const kw = {
        bindings: {
            bold: Co("bold"),
            italic: Co("italic"),
            underline: Co("underline"),
            indent: {
                key: "Tab",
                format: ["blockquote", "indent", "list"],
                handler(t, e) {
                    return e.collapsed && e.offset !== 0
                        ? !0
                        : (this.quill.format("indent", "+1", L.sources.USER),
                          !1);
                },
            },
            outdent: {
                key: "Tab",
                shiftKey: !0,
                format: ["blockquote", "indent", "list"],
                handler(t, e) {
                    return e.collapsed && e.offset !== 0
                        ? !0
                        : (this.quill.format("indent", "-1", L.sources.USER),
                          !1);
                },
            },
            "outdent backspace": {
                key: "Backspace",
                collapsed: !0,
                shiftKey: null,
                metaKey: null,
                ctrlKey: null,
                altKey: null,
                format: ["indent", "list"],
                offset: 0,
                handler(t, e) {
                    e.format.indent != null
                        ? this.quill.format("indent", "-1", L.sources.USER)
                        : e.format.list != null &&
                          this.quill.format("list", !1, L.sources.USER);
                },
            },
            "indent code-block": P0(!0),
            "outdent code-block": P0(!1),
            "remove tab": {
                key: "Tab",
                shiftKey: !0,
                collapsed: !0,
                prefix: /\t$/,
                handler(t) {
                    this.quill.deleteText(t.index - 1, 1, L.sources.USER);
                },
            },
            tab: {
                key: "Tab",
                handler(t, e) {
                    if (e.format.table) return !0;
                    this.quill.history.cutoff();
                    const r = new z()
                        .retain(t.index)
                        .delete(t.length)
                        .insert("	");
                    return (
                        this.quill.updateContents(r, L.sources.USER),
                        this.quill.history.cutoff(),
                        this.quill.setSelection(t.index + 1, L.sources.SILENT),
                        !1
                    );
                },
            },
            "blockquote empty enter": {
                key: "Enter",
                collapsed: !0,
                format: ["blockquote"],
                empty: !0,
                handler() {
                    this.quill.format("blockquote", !1, L.sources.USER);
                },
            },
            "list empty enter": {
                key: "Enter",
                collapsed: !0,
                format: ["list"],
                empty: !0,
                handler(t, e) {
                    const r = { list: !1 };
                    (e.format.indent && (r.indent = !1),
                        this.quill.formatLine(
                            t.index,
                            t.length,
                            r,
                            L.sources.USER,
                        ));
                },
            },
            "checklist enter": {
                key: "Enter",
                collapsed: !0,
                format: { list: "checked" },
                handler(t) {
                    const [e, r] = this.quill.getLine(t.index),
                        n = { ...e.formats(), list: "checked" },
                        s = new z()
                            .retain(t.index)
                            .insert(
                                `
`,
                                n,
                            )
                            .retain(e.length() - r - 1)
                            .retain(1, { list: "unchecked" });
                    (this.quill.updateContents(s, L.sources.USER),
                        this.quill.setSelection(t.index + 1, L.sources.SILENT),
                        this.quill.scrollSelectionIntoView());
                },
            },
            "header enter": {
                key: "Enter",
                collapsed: !0,
                format: ["header"],
                suffix: /^$/,
                handler(t, e) {
                    const [r, n] = this.quill.getLine(t.index),
                        s = new z()
                            .retain(t.index)
                            .insert(
                                `
`,
                                e.format,
                            )
                            .retain(r.length() - n - 1)
                            .retain(1, { header: null });
                    (this.quill.updateContents(s, L.sources.USER),
                        this.quill.setSelection(t.index + 1, L.sources.SILENT),
                        this.quill.scrollSelectionIntoView());
                },
            },
            "table backspace": {
                key: "Backspace",
                format: ["table"],
                collapsed: !0,
                offset: 0,
                handler() {},
            },
            "table delete": {
                key: "Delete",
                format: ["table"],
                collapsed: !0,
                suffix: /^$/,
                handler() {},
            },
            "table enter": {
                key: "Enter",
                shiftKey: null,
                format: ["table"],
                handler(t) {
                    const e = this.quill.getModule("table");
                    if (e) {
                        const [r, n, s, i] = e.getTable(t),
                            o = Pw(r, n, s, i);
                        if (o == null) return;
                        let a = r.offset();
                        if (o < 0) {
                            const l = new z().retain(a).insert(`
`);
                            (this.quill.updateContents(l, L.sources.USER),
                                this.quill.setSelection(
                                    t.index + 1,
                                    t.length,
                                    L.sources.SILENT,
                                ));
                        } else if (o > 0) {
                            a += r.length();
                            const l = new z().retain(a).insert(`
`);
                            (this.quill.updateContents(l, L.sources.USER),
                                this.quill.setSelection(a, L.sources.USER));
                        }
                    }
                },
            },
            "table tab": {
                key: "Tab",
                shiftKey: null,
                format: ["table"],
                handler(t, e) {
                    const { event: r, line: n } = e,
                        s = n.offset(this.quill.scroll);
                    r.shiftKey
                        ? this.quill.setSelection(s - 1, L.sources.USER)
                        : this.quill.setSelection(
                              s + n.length(),
                              L.sources.USER,
                          );
                },
            },
            "list autofill": {
                key: " ",
                shiftKey: null,
                collapsed: !0,
                format: { "code-block": !1, blockquote: !1, table: !1 },
                prefix: /^\s*?(\d+\.|-|\*|\[ ?\]|\[x\])$/,
                handler(t, e) {
                    if (this.quill.scroll.query("list") == null) return !0;
                    const { length: r } = e.prefix,
                        [n, s] = this.quill.getLine(t.index);
                    if (s > r) return !0;
                    let i;
                    switch (e.prefix.trim()) {
                        case "[]":
                        case "[ ]":
                            i = "unchecked";
                            break;
                        case "[x]":
                            i = "checked";
                            break;
                        case "-":
                        case "*":
                            i = "bullet";
                            break;
                        default:
                            i = "ordered";
                    }
                    (this.quill.insertText(t.index, " ", L.sources.USER),
                        this.quill.history.cutoff());
                    const o = new z()
                        .retain(t.index - s)
                        .delete(r + 1)
                        .retain(n.length() - 2 - s)
                        .retain(1, { list: i });
                    return (
                        this.quill.updateContents(o, L.sources.USER),
                        this.quill.history.cutoff(),
                        this.quill.setSelection(t.index - r, L.sources.SILENT),
                        !1
                    );
                },
            },
            "code exit": {
                key: "Enter",
                collapsed: !0,
                format: ["code-block"],
                prefix: /^$/,
                suffix: /^\s*$/,
                handler(t) {
                    const [e, r] = this.quill.getLine(t.index);
                    let n = 2,
                        s = e;
                    for (
                        ;
                        s != null &&
                        s.length() <= 1 &&
                        s.formats()["code-block"];
                    )
                        if (((s = s.prev), (n -= 1), n <= 0)) {
                            const i = new z()
                                .retain(t.index + e.length() - r - 2)
                                .retain(1, { "code-block": null })
                                .delete(1);
                            return (
                                this.quill.updateContents(i, L.sources.USER),
                                this.quill.setSelection(
                                    t.index - 1,
                                    L.sources.SILENT,
                                ),
                                !1
                            );
                        }
                    return !0;
                },
            },
            "embed left": Bs("ArrowLeft", !1),
            "embed left shift": Bs("ArrowLeft", !0),
            "embed right": Bs("ArrowRight", !1),
            "embed right shift": Bs("ArrowRight", !0),
            "table down": M0(!1),
            "table up": M0(!0),
        },
    };
    vi.DEFAULTS = kw;
    function P0(t) {
        return {
            key: "Tab",
            shiftKey: !t,
            format: { "code-block": !0 },
            handler(e, r) {
                let { event: n } = r;
                const s = this.quill.scroll.query("code-block"),
                    { TAB: i } = s;
                if (e.length === 0 && !n.shiftKey) {
                    (this.quill.insertText(e.index, i, L.sources.USER),
                        this.quill.setSelection(
                            e.index + i.length,
                            L.sources.SILENT,
                        ));
                    return;
                }
                const o =
                    e.length === 0
                        ? this.quill.getLines(e.index, 1)
                        : this.quill.getLines(e);
                let { index: a, length: l } = e;
                (o.forEach((f, u) => {
                    t
                        ? (f.insertAt(0, i),
                          u === 0 ? (a += i.length) : (l += i.length))
                        : f.domNode.textContent.startsWith(i) &&
                          (f.deleteAt(0, i.length),
                          u === 0 ? (a -= i.length) : (l -= i.length));
                }),
                    this.quill.update(L.sources.USER),
                    this.quill.setSelection(a, l, L.sources.SILENT));
            },
        };
    }
    function Bs(t, e) {
        return {
            key: t,
            shiftKey: e,
            altKey: null,
            [t === "ArrowLeft" ? "prefix" : "suffix"]: /^$/,
            handler(n) {
                let { index: s } = n;
                t === "ArrowRight" && (s += n.length + 1);
                const [i] = this.quill.getLeaf(s);
                return i instanceof He
                    ? (t === "ArrowLeft"
                          ? e
                              ? this.quill.setSelection(
                                    n.index - 1,
                                    n.length + 1,
                                    L.sources.USER,
                                )
                              : this.quill.setSelection(
                                    n.index - 1,
                                    L.sources.USER,
                                )
                          : e
                            ? this.quill.setSelection(
                                  n.index,
                                  n.length + 1,
                                  L.sources.USER,
                              )
                            : this.quill.setSelection(
                                  n.index + n.length + 1,
                                  L.sources.USER,
                              ),
                      !1)
                    : !0;
            },
        };
    }
    function Co(t) {
        return {
            key: t[0],
            shortKey: !0,
            handler(e, r) {
                this.quill.format(t, !r.format[t], L.sources.USER);
            },
        };
    }
    function M0(t) {
        return {
            key: t ? "ArrowUp" : "ArrowDown",
            collapsed: !0,
            format: ["table"],
            handler(e, r) {
                const n = t ? "prev" : "next",
                    s = r.line,
                    i = s.parent[n];
                if (i != null) {
                    if (i.statics.blotName === "table-row") {
                        let o = i.children.head,
                            a = s;
                        for (; a.prev != null; ) ((a = a.prev), (o = o.next));
                        const l =
                            o.offset(this.quill.scroll) +
                            Math.min(r.offset, o.length() - 1);
                        this.quill.setSelection(l, 0, L.sources.USER);
                    }
                } else {
                    const o = s.table()[n];
                    o != null &&
                        (t
                            ? this.quill.setSelection(
                                  o.offset(this.quill.scroll) + o.length() - 1,
                                  0,
                                  L.sources.USER,
                              )
                            : this.quill.setSelection(
                                  o.offset(this.quill.scroll),
                                  0,
                                  L.sources.USER,
                              ));
                }
                return !1;
            },
        };
    }
    function Iw(t) {
        if (typeof t == "string" || typeof t == "number") t = { key: t };
        else if (typeof t == "object") t = Kr(t);
        else return null;
        return (t.shortKey && ((t[qw] = t.shortKey), delete t.shortKey), t);
    }
    function ol(t) {
        let { quill: e, range: r } = t;
        const n = e.getLines(r);
        let s = {};
        if (n.length > 1) {
            const i = n[0].formats(),
                o = n[n.length - 1].formats();
            s = Ze.AttributeMap.diff(o, i) || {};
        }
        (e.deleteText(r, L.sources.USER),
            Object.keys(s).length > 0 &&
                e.formatLine(r.index, 1, s, L.sources.USER),
            e.setSelection(r.index, L.sources.SILENT));
    }
    function Pw(t, e, r, n) {
        return e.prev == null && e.next == null
            ? r.prev == null && r.next == null
                ? n === 0
                    ? -1
                    : 1
                : r.prev == null
                  ? -1
                  : 1
            : e.prev == null
              ? -1
              : e.next == null
                ? 1
                : null;
    }
    const Mw = /font-weight:\s*normal/,
        Bw = ["P", "OL", "UL"],
        B0 = (t) => t && Bw.includes(t.tagName),
        $w = (t) => {
            Array.from(t.querySelectorAll("br"))
                .filter(
                    (e) =>
                        B0(e.previousElementSibling) &&
                        B0(e.nextElementSibling),
                )
                .forEach((e) => {
                    e.parentNode?.removeChild(e);
                });
        },
        Dw = (t) => {
            Array.from(t.querySelectorAll('b[style*="font-weight"]'))
                .filter((e) => e.getAttribute("style")?.match(Mw))
                .forEach((e) => {
                    const r = t.createDocumentFragment();
                    (r.append(...e.childNodes),
                        e.parentNode?.replaceChild(r, e));
                });
        };
    function jw(t) {
        t.querySelector('[id^="docs-internal-guid-"]') && (Dw(t), $w(t));
    }
    const Uw = /\bmso-list:[^;]*ignore/i,
        Fw = /\bmso-list:[^;]*\bl(\d+)/i,
        Hw = /\bmso-list:[^;]*\blevel(\d+)/i,
        zw = (t, e) => {
            const r = t.getAttribute("style"),
                n = r?.match(Fw);
            if (!n) return null;
            const s = Number(n[1]),
                i = r?.match(Hw),
                o = i ? Number(i[1]) : 1,
                a = new RegExp(
                    `@list l${s}:level${o}\\s*\\{[^\\}]*mso-level-number-format:\\s*([\\w-]+)`,
                    "i",
                ),
                l = e.match(a),
                f = l && l[1] === "bullet" ? "bullet" : "ordered";
            return { id: s, indent: o, type: f, element: t };
        },
        Kw = (t) => {
            const e = Array.from(t.querySelectorAll("[style*=mso-list]")),
                r = [],
                n = [];
            (e.forEach((o) => {
                (o.getAttribute("style") || "").match(Uw)
                    ? r.push(o)
                    : n.push(o);
            }),
                r.forEach((o) => o.parentNode?.removeChild(o)));
            const s = t.documentElement.innerHTML,
                i = n.map((o) => zw(o, s)).filter((o) => o);
            for (; i.length; ) {
                const o = [];
                let a = i.shift();
                for (; a; )
                    (o.push(a),
                        (a =
                            i.length &&
                            i[0]?.element === a.element.nextElementSibling &&
                            i[0].id === a.id
                                ? i.shift()
                                : null));
                const l = document.createElement("ul");
                o.forEach((d) => {
                    const h = document.createElement("li");
                    (h.setAttribute("data-list", d.type),
                        d.indent > 1 &&
                            h.setAttribute(
                                "class",
                                `ql-indent-${d.indent - 1}`,
                            ),
                        (h.innerHTML = d.element.innerHTML),
                        l.appendChild(h));
                });
                const f = o[0]?.element,
                    { parentNode: u } = f ?? {};
                (f && u?.replaceChild(l, f),
                    o.slice(1).forEach((d) => {
                        let { element: h } = d;
                        u?.removeChild(h);
                    }));
            }
        };
    function Ww(t) {
        t.documentElement.getAttribute("xmlns:w") ===
            "urn:schemas-microsoft-com:office:word" && Kw(t);
    }
    const Vw = [Ww, jw],
        Gw = (t) => {
            t.documentElement &&
                Vw.forEach((e) => {
                    e(t);
                });
        },
        Zw = qt("quill:clipboard"),
        Xw = [
            [Node.TEXT_NODE, fE],
            [Node.TEXT_NODE, D0],
            ["br", rE],
            [Node.ELEMENT_NODE, D0],
            [Node.ELEMENT_NODE, tE],
            [Node.ELEMENT_NODE, eE],
            [Node.ELEMENT_NODE, aE],
            ["li", iE],
            ["ol, ul", oE],
            ["pre", nE],
            ["tr", lE],
            ["b", Lo("bold")],
            ["i", Lo("italic")],
            ["strike", Lo("strike")],
            ["style", sE],
        ],
        Jw = [Nw, Eu].reduce((t, e) => ((t[e.keyName] = e), t), {}),
        $0 = [vu, nl, rl, xu, Tu, Ou].reduce(
            (t, e) => ((t[e.keyName] = e), t),
            {},
        );
    class Yw extends wt {
        static DEFAULTS = { matchers: [] };
        constructor(e, r) {
            (super(e, r),
                this.quill.root.addEventListener("copy", (n) =>
                    this.onCaptureCopy(n, !1),
                ),
                this.quill.root.addEventListener("cut", (n) =>
                    this.onCaptureCopy(n, !0),
                ),
                this.quill.root.addEventListener(
                    "paste",
                    this.onCapturePaste.bind(this),
                ),
                (this.matchers = []),
                Xw.concat(this.options.matchers ?? []).forEach((n) => {
                    let [s, i] = n;
                    this.addMatcher(s, i);
                }));
        }
        addMatcher(e, r) {
            this.matchers.push([e, r]);
        }
        convert(e) {
            let { html: r, text: n } = e,
                s =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : {};
            if (s[Be.blotName])
                return new z().insert(n || "", {
                    [Be.blotName]: s[Be.blotName],
                });
            if (!r) return new z().insert(n || "", s);
            const i = this.convertHTML(r);
            return ts(
                i,
                `
`,
            ) &&
                (i.ops[i.ops.length - 1].attributes == null || s.table)
                ? i.compose(new z().retain(i.length() - 1).delete(1))
                : i;
        }
        normalizeHTML(e) {
            Gw(e);
        }
        convertHTML(e) {
            const r = new DOMParser().parseFromString(e, "text/html");
            this.normalizeHTML(r);
            const n = r.body,
                s = new WeakMap(),
                [i, o] = this.prepareMatching(n, s);
            return al(this.quill.scroll, n, i, o, s);
        }
        dangerouslyPasteHTML(e, r) {
            let n =
                arguments.length > 2 && arguments[2] !== void 0
                    ? arguments[2]
                    : L.sources.API;
            if (typeof e == "string") {
                const s = this.convert({ html: e, text: "" });
                (this.quill.setContents(s, r),
                    this.quill.setSelection(0, L.sources.SILENT));
            } else {
                const s = this.convert({ html: r, text: "" });
                (this.quill.updateContents(new z().retain(e).concat(s), n),
                    this.quill.setSelection(e + s.length(), L.sources.SILENT));
            }
        }
        onCaptureCopy(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : !1;
            if (e.defaultPrevented) return;
            e.preventDefault();
            const [n] = this.quill.selection.getRange();
            if (n == null) return;
            const { html: s, text: i } = this.onCopy(n, r);
            (e.clipboardData?.setData("text/plain", i),
                e.clipboardData?.setData("text/html", s),
                r && ol({ range: n, quill: this.quill }));
        }
        normalizeURIList(e) {
            return e.split(/\r?\n/).filter((r) => r[0] !== "#").join(`
`);
        }
        onCapturePaste(e) {
            if (e.defaultPrevented || !this.quill.isEnabled()) return;
            e.preventDefault();
            const r = this.quill.getSelection(!0);
            if (r == null) return;
            const n = e.clipboardData?.getData("text/html");
            let s = e.clipboardData?.getData("text/plain");
            if (!n && !s) {
                const o = e.clipboardData?.getData("text/uri-list");
                o && (s = this.normalizeURIList(o));
            }
            const i = Array.from(e.clipboardData?.files || []);
            if (!n && i.length > 0) {
                this.quill.uploader.upload(r, i);
                return;
            }
            if (n && i.length > 0) {
                const o = new DOMParser().parseFromString(n, "text/html");
                if (
                    o.body.childElementCount === 1 &&
                    o.body.firstElementChild?.tagName === "IMG"
                ) {
                    this.quill.uploader.upload(r, i);
                    return;
                }
            }
            this.onPaste(r, { html: n, text: s });
        }
        onCopy(e) {
            const r = this.quill.getText(e);
            return { html: this.quill.getSemanticHTML(e), text: r };
        }
        onPaste(e, r) {
            let { text: n, html: s } = r;
            const i = this.quill.getFormat(e.index),
                o = this.convert({ text: n, html: s }, i);
            Zw.log("onPaste", o, { text: n, html: s });
            const a = new z().retain(e.index).delete(e.length).concat(o);
            (this.quill.updateContents(a, L.sources.USER),
                this.quill.setSelection(
                    a.length() - e.length,
                    L.sources.SILENT,
                ),
                this.quill.scrollSelectionIntoView());
        }
        prepareMatching(e, r) {
            const n = [],
                s = [];
            return (
                this.matchers.forEach((i) => {
                    const [o, a] = i;
                    switch (o) {
                        case Node.TEXT_NODE:
                            s.push(a);
                            break;
                        case Node.ELEMENT_NODE:
                            n.push(a);
                            break;
                        default:
                            Array.from(e.querySelectorAll(o)).forEach((l) => {
                                r.has(l) ? r.get(l)?.push(a) : r.set(l, [a]);
                            });
                            break;
                    }
                }),
                [n, s]
            );
        }
    }
    function Tr(t, e, r, n) {
        return n.query(e)
            ? t.reduce((s, i) => {
                  if (!i.insert) return s;
                  if (i.attributes && i.attributes[e]) return s.push(i);
                  const o = r ? { [e]: r } : {};
                  return s.insert(i.insert, { ...o, ...i.attributes });
              }, new z())
            : t;
    }
    function ts(t, e) {
        let r = "";
        for (let n = t.ops.length - 1; n >= 0 && r.length < e.length; --n) {
            const s = t.ops[n];
            if (typeof s.insert != "string") break;
            r = s.insert + r;
        }
        return r.slice(-1 * e.length) === e;
    }
    function Dt(t, e) {
        if (!(t instanceof Element)) return !1;
        const r = e.query(t);
        return r && r.prototype instanceof He
            ? !1
            : [
                  "address",
                  "article",
                  "blockquote",
                  "canvas",
                  "dd",
                  "div",
                  "dl",
                  "dt",
                  "fieldset",
                  "figcaption",
                  "figure",
                  "footer",
                  "form",
                  "h1",
                  "h2",
                  "h3",
                  "h4",
                  "h5",
                  "h6",
                  "header",
                  "iframe",
                  "li",
                  "main",
                  "nav",
                  "ol",
                  "output",
                  "p",
                  "pre",
                  "section",
                  "table",
                  "td",
                  "tr",
                  "ul",
                  "video",
              ].includes(t.tagName.toLowerCase());
    }
    function Qw(t, e) {
        return (
            t.previousElementSibling &&
            t.nextElementSibling &&
            !Dt(t.previousElementSibling, e) &&
            !Dt(t.nextElementSibling, e)
        );
    }
    const $s = new WeakMap();
    function Cu(t) {
        return t == null
            ? !1
            : ($s.has(t) ||
                  (t.tagName === "PRE"
                      ? $s.set(t, !0)
                      : $s.set(t, Cu(t.parentNode))),
              $s.get(t));
    }
    function al(t, e, r, n, s) {
        return e.nodeType === e.TEXT_NODE
            ? n.reduce((i, o) => o(e, i, t), new z())
            : e.nodeType === e.ELEMENT_NODE
              ? Array.from(e.childNodes || []).reduce((i, o) => {
                    let a = al(t, o, r, n, s);
                    return (
                        o.nodeType === e.ELEMENT_NODE &&
                            ((a = r.reduce((l, f) => f(o, l, t), a)),
                            (a = (s.get(o) || []).reduce(
                                (l, f) => f(o, l, t),
                                a,
                            ))),
                        i.concat(a)
                    );
                }, new z())
              : new z();
    }
    function Lo(t) {
        return (e, r, n) => Tr(r, t, !0, n);
    }
    function eE(t, e, r) {
        const n = yt.keys(t),
            s = lt.keys(t),
            i = Vt.keys(t),
            o = {};
        return (
            n
                .concat(s)
                .concat(i)
                .forEach((a) => {
                    let l = r.query(a, K.ATTRIBUTE);
                    (l != null &&
                        ((o[l.attrName] = l.value(t)), o[l.attrName])) ||
                        ((l = Jw[a]),
                        l != null &&
                            (l.attrName === a || l.keyName === a) &&
                            (o[l.attrName] = l.value(t) || void 0),
                        (l = $0[a]),
                        l != null &&
                            (l.attrName === a || l.keyName === a) &&
                            ((l = $0[a]),
                            (o[l.attrName] = l.value(t) || void 0)));
                }),
            Object.entries(o).reduce((a, l) => {
                let [f, u] = l;
                return Tr(a, f, u, r);
            }, e)
        );
    }
    function tE(t, e, r) {
        const n = r.query(t);
        if (n == null) return e;
        if (n.prototype instanceof He) {
            const s = {},
                i = n.value(t);
            if (i != null)
                return (
                    (s[n.blotName] = i),
                    new z().insert(s, n.formats(t, r))
                );
        } else if (
            (n.prototype instanceof Wn &&
                !ts(
                    e,
                    `
`,
                ) &&
                e.insert(`
`),
            "blotName" in n && "formats" in n && typeof n.formats == "function")
        )
            return Tr(e, n.blotName, n.formats(t, r), r);
        return e;
    }
    function rE(t, e) {
        return (
            ts(
                e,
                `
`,
            ) ||
                e.insert(`
`),
            e
        );
    }
    function nE(t, e, r) {
        const n = r.query("code-block"),
            s =
                n && "formats" in n && typeof n.formats == "function"
                    ? n.formats(t, r)
                    : !0;
        return Tr(e, "code-block", s, r);
    }
    function sE() {
        return new z();
    }
    function iE(t, e, r) {
        const n = r.query(t);
        if (
            n == null ||
            n.blotName !== "list" ||
            !ts(
                e,
                `
`,
            )
        )
            return e;
        let s = -1,
            i = t.parentNode;
        for (; i != null; )
            (["OL", "UL"].includes(i.tagName) && (s += 1), (i = i.parentNode));
        return s <= 0
            ? e
            : e.reduce(
                  (o, a) =>
                      a.insert
                          ? a.attributes &&
                            typeof a.attributes.indent == "number"
                              ? o.push(a)
                              : o.insert(a.insert, {
                                    indent: s,
                                    ...(a.attributes || {}),
                                })
                          : o,
                  new z(),
              );
    }
    function oE(t, e, r) {
        const n = t;
        let s = n.tagName === "OL" ? "ordered" : "bullet";
        const i = n.getAttribute("data-checked");
        return (
            i && (s = i === "true" ? "checked" : "unchecked"),
            Tr(e, "list", s, r)
        );
    }
    function D0(t, e, r) {
        if (
            !ts(
                e,
                `
`,
            )
        ) {
            if (
                Dt(t, r) &&
                (t.childNodes.length > 0 || t instanceof HTMLParagraphElement)
            )
                return e.insert(`
`);
            if (e.length() > 0 && t.nextSibling) {
                let n = t.nextSibling;
                for (; n != null; ) {
                    if (Dt(n, r))
                        return e.insert(`
`);
                    const s = r.query(n);
                    if (s && s.prototype instanceof Ge)
                        return e.insert(`
`);
                    n = n.firstChild;
                }
            }
        }
        return e;
    }
    function aE(t, e, r) {
        const n = {},
            s = t.style || {};
        return (
            s.fontStyle === "italic" && (n.italic = !0),
            s.textDecoration === "underline" && (n.underline = !0),
            s.textDecoration === "line-through" && (n.strike = !0),
            (s.fontWeight?.startsWith("bold") ||
                parseInt(s.fontWeight, 10) >= 700) &&
                (n.bold = !0),
            (e = Object.entries(n).reduce((i, o) => {
                let [a, l] = o;
                return Tr(i, a, l, r);
            }, e)),
            parseFloat(s.textIndent || 0) > 0 ? new z().insert("	").concat(e) : e
        );
    }
    function lE(t, e, r) {
        const n =
            t.parentElement?.tagName === "TABLE"
                ? t.parentElement
                : t.parentElement?.parentElement;
        if (n != null) {
            const i = Array.from(n.querySelectorAll("tr")).indexOf(t) + 1;
            return Tr(e, "table", i, r);
        }
        return e;
    }
    function fE(t, e, r) {
        let n = t.data;
        if (t.parentElement?.tagName === "O:P") return e.insert(n.trim());
        if (!Cu(t)) {
            if (
                n.trim().length === 0 &&
                n.includes(`
`) &&
                !Qw(t, r)
            )
                return e;
            ((n = n.replace(/[^\S\u00a0]/g, " ")),
                (n = n.replace(/ {2,}/g, " ")),
                ((t.previousSibling == null &&
                    t.parentElement != null &&
                    Dt(t.parentElement, r)) ||
                    (t.previousSibling instanceof Element &&
                        Dt(t.previousSibling, r))) &&
                    (n = n.replace(/^ /, "")),
                ((t.nextSibling == null &&
                    t.parentElement != null &&
                    Dt(t.parentElement, r)) ||
                    (t.nextSibling instanceof Element &&
                        Dt(t.nextSibling, r))) &&
                    (n = n.replace(/ $/, "")),
                (n = n.replaceAll(" ", " ")));
        }
        return e.insert(n);
    }
    class cE extends wt {
        static DEFAULTS = { delay: 1e3, maxStack: 100, userOnly: !1 };
        lastRecorded = 0;
        ignoreChange = !1;
        stack = { undo: [], redo: [] };
        currentRange = null;
        constructor(e, r) {
            (super(e, r),
                this.quill.on(L.events.EDITOR_CHANGE, (n, s, i, o) => {
                    n === L.events.SELECTION_CHANGE
                        ? s && o !== L.sources.SILENT && (this.currentRange = s)
                        : n === L.events.TEXT_CHANGE &&
                          (this.ignoreChange ||
                              (!this.options.userOnly || o === L.sources.USER
                                  ? this.record(s, i)
                                  : this.transform(s)),
                          (this.currentRange = ha(this.currentRange, s)));
                }),
                this.quill.keyboard.addBinding(
                    { key: "z", shortKey: !0 },
                    this.undo.bind(this),
                ),
                this.quill.keyboard.addBinding(
                    { key: ["z", "Z"], shortKey: !0, shiftKey: !0 },
                    this.redo.bind(this),
                ),
                /Win/i.test(navigator.platform) &&
                    this.quill.keyboard.addBinding(
                        { key: "y", shortKey: !0 },
                        this.redo.bind(this),
                    ),
                this.quill.root.addEventListener("beforeinput", (n) => {
                    n.inputType === "historyUndo"
                        ? (this.undo(), n.preventDefault())
                        : n.inputType === "historyRedo" &&
                          (this.redo(), n.preventDefault());
                }));
        }
        change(e, r) {
            if (this.stack[e].length === 0) return;
            const n = this.stack[e].pop();
            if (!n) return;
            const s = this.quill.getContents(),
                i = n.delta.invert(s);
            (this.stack[r].push({ delta: i, range: ha(n.range, i) }),
                (this.lastRecorded = 0),
                (this.ignoreChange = !0),
                this.quill.updateContents(n.delta, L.sources.USER),
                (this.ignoreChange = !1),
                this.restoreSelection(n));
        }
        clear() {
            this.stack = { undo: [], redo: [] };
        }
        cutoff() {
            this.lastRecorded = 0;
        }
        record(e, r) {
            if (e.ops.length === 0) return;
            this.stack.redo = [];
            let n = e.invert(r),
                s = this.currentRange;
            const i = Date.now();
            if (
                this.lastRecorded + this.options.delay > i &&
                this.stack.undo.length > 0
            ) {
                const o = this.stack.undo.pop();
                o && ((n = n.compose(o.delta)), (s = o.range));
            } else this.lastRecorded = i;
            n.length() !== 0 &&
                (this.stack.undo.push({ delta: n, range: s }),
                this.stack.undo.length > this.options.maxStack &&
                    this.stack.undo.shift());
        }
        redo() {
            this.change("redo", "undo");
        }
        transform(e) {
            (j0(this.stack.undo, e), j0(this.stack.redo, e));
        }
        undo() {
            this.change("undo", "redo");
        }
        restoreSelection(e) {
            if (e.range) this.quill.setSelection(e.range, L.sources.USER);
            else {
                const r = hE(this.quill.scroll, e.delta);
                this.quill.setSelection(r, L.sources.USER);
            }
        }
    }
    function j0(t, e) {
        let r = e;
        for (let n = t.length - 1; n >= 0; n -= 1) {
            const s = t[n];
            ((t[n] = {
                delta: r.transform(s.delta, !0),
                range: s.range && ha(s.range, r),
            }),
                (r = s.delta.transform(r)),
                t[n].delta.length() === 0 && t.splice(n, 1));
        }
    }
    function uE(t, e) {
        const r = e.ops[e.ops.length - 1];
        return r == null
            ? !1
            : r.insert != null
              ? typeof r.insert == "string" &&
                r.insert.endsWith(`
`)
              : r.attributes != null
                ? Object.keys(r.attributes).some(
                      (n) => t.query(n, K.BLOCK) != null,
                  )
                : !1;
    }
    function hE(t, e) {
        const r = e.reduce((s, i) => s + (i.delete || 0), 0);
        let n = e.length() - r;
        return (uE(t, e) && (n -= 1), n);
    }
    function ha(t, e) {
        if (!t) return t;
        const r = e.transformPosition(t.index),
            n = e.transformPosition(t.index + t.length);
        return { index: r, length: n - r };
    }
    class Lu extends wt {
        constructor(e, r) {
            (super(e, r),
                e.root.addEventListener("drop", (n) => {
                    n.preventDefault();
                    let s = null;
                    if (document.caretRangeFromPoint)
                        s = document.caretRangeFromPoint(n.clientX, n.clientY);
                    else if (document.caretPositionFromPoint) {
                        const o = document.caretPositionFromPoint(
                            n.clientX,
                            n.clientY,
                        );
                        ((s = document.createRange()),
                            s.setStart(o.offsetNode, o.offset),
                            s.setEnd(o.offsetNode, o.offset));
                    }
                    const i = s && e.selection.normalizeNative(s);
                    if (i) {
                        const o = e.selection.normalizedToRange(i);
                        n.dataTransfer?.files &&
                            this.upload(o, n.dataTransfer.files);
                    }
                }));
        }
        upload(e, r) {
            const n = [];
            (Array.from(r).forEach((s) => {
                s && this.options.mimetypes?.includes(s.type) && n.push(s);
            }),
                n.length > 0 && this.options.handler.call(this, e, n));
        }
    }
    Lu.DEFAULTS = {
        mimetypes: ["image/png", "image/jpeg"],
        handler(t, e) {
            if (!this.quill.scroll.query("image")) return;
            const r = e.map(
                (n) =>
                    new Promise((s) => {
                        const i = new FileReader();
                        ((i.onload = () => {
                            s(i.result);
                        }),
                            i.readAsDataURL(n));
                    }),
            );
            Promise.all(r).then((n) => {
                const s = n.reduce(
                    (i, o) => i.insert({ image: o }),
                    new z().retain(t.index).delete(t.length),
                );
                (this.quill.updateContents(s, H.sources.USER),
                    this.quill.setSelection(
                        t.index + n.length,
                        H.sources.SILENT,
                    ));
            });
        },
    };
    const dE = ["insertText", "insertReplacementText"];
    class pE extends wt {
        constructor(e, r) {
            (super(e, r),
                e.root.addEventListener("beforeinput", (n) => {
                    this.handleBeforeInput(n);
                }),
                /Android/i.test(navigator.userAgent) ||
                    e.on(L.events.COMPOSITION_BEFORE_START, () => {
                        this.handleCompositionStart();
                    }));
        }
        deleteRange(e) {
            ol({ range: e, quill: this.quill });
        }
        replaceText(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : "";
            if (e.length === 0) return !1;
            if (r) {
                const n = this.quill.getFormat(e.index, 1);
                (this.deleteRange(e),
                    this.quill.updateContents(
                        new z().retain(e.index).insert(r, n),
                        L.sources.USER,
                    ));
            } else this.deleteRange(e);
            return (
                this.quill.setSelection(
                    e.index + r.length,
                    0,
                    L.sources.SILENT,
                ),
                !0
            );
        }
        handleBeforeInput(e) {
            if (
                this.quill.composition.isComposing ||
                e.defaultPrevented ||
                !dE.includes(e.inputType)
            )
                return;
            const r = e.getTargetRanges ? e.getTargetRanges()[0] : null;
            if (!r || r.collapsed === !0) return;
            const n = gE(e);
            if (n == null) return;
            const s = this.quill.selection.normalizeNative(r),
                i = s ? this.quill.selection.normalizedToRange(s) : null;
            i && this.replaceText(i, n) && e.preventDefault();
        }
        handleCompositionStart() {
            const e = this.quill.getSelection();
            e && this.replaceText(e);
        }
    }
    function gE(t) {
        return typeof t.data == "string"
            ? t.data
            : t.dataTransfer?.types.includes("text/plain")
              ? t.dataTransfer.getData("text/plain")
              : null;
    }
    const mE = /Mac/i.test(navigator.platform),
        bE = 100,
        yE = (t) =>
            !!(
                t.key === "ArrowLeft" ||
                t.key === "ArrowRight" ||
                t.key === "ArrowUp" ||
                t.key === "ArrowDown" ||
                t.key === "Home" ||
                (mE && t.key === "a" && t.ctrlKey === !0)
            );
    class vE extends wt {
        isListening = !1;
        selectionChangeDeadline = 0;
        constructor(e, r) {
            (super(e, r),
                this.handleArrowKeys(),
                this.handleNavigationShortcuts());
        }
        handleArrowKeys() {
            this.quill.keyboard.addBinding({
                key: ["ArrowLeft", "ArrowRight"],
                offset: 0,
                shiftKey: null,
                handler(e, r) {
                    let { line: n, event: s } = r;
                    if (!(n instanceof nt) || !n.uiNode) return !0;
                    const i = getComputedStyle(n.domNode).direction === "rtl";
                    return (i && s.key !== "ArrowRight") ||
                        (!i && s.key !== "ArrowLeft")
                        ? !0
                        : (this.quill.setSelection(
                              e.index - 1,
                              e.length + (s.shiftKey ? 1 : 0),
                              L.sources.USER,
                          ),
                          !1);
                },
            });
        }
        handleNavigationShortcuts() {
            this.quill.root.addEventListener("keydown", (e) => {
                !e.defaultPrevented &&
                    yE(e) &&
                    this.ensureListeningToSelectionChange();
            });
        }
        ensureListeningToSelectionChange() {
            if (
                ((this.selectionChangeDeadline = Date.now() + bE),
                this.isListening)
            )
                return;
            this.isListening = !0;
            const e = () => {
                ((this.isListening = !1),
                    Date.now() <= this.selectionChangeDeadline &&
                        this.handleSelectionChange());
            };
            document.addEventListener("selectionchange", e, { once: !0 });
        }
        handleSelectionChange() {
            const e = document.getSelection();
            if (!e) return;
            const r = e.getRangeAt(0);
            if (r.collapsed !== !0 || r.startOffset !== 0) return;
            const n = this.quill.scroll.find(r.startContainer);
            if (!(n instanceof nt) || !n.uiNode) return;
            const s = document.createRange();
            (s.setStartAfter(n.uiNode),
                s.setEndAfter(n.uiNode),
                e.removeAllRanges(),
                e.addRange(s));
        }
    }
    L.register({
        "blots/block": Ne,
        "blots/block/embed": Ge,
        "blots/break": ft,
        "blots/container": Ar,
        "blots/cursor": tt,
        "blots/embed": el,
        "blots/inline": Pe,
        "blots/scroll": Tw,
        "blots/text": it,
        "modules/clipboard": Yw,
        "modules/history": cE,
        "modules/keyboard": vi,
        "modules/uploader": Lu,
        "modules/input": pE,
        "modules/uiNode": vE,
    });
    class wE extends lt {
        add(e, r) {
            let n = 0;
            if (r === "+1" || r === "-1") {
                const s = this.value(e) || 0;
                n = r === "+1" ? s + 1 : s - 1;
            } else typeof r == "number" && (n = r);
            return n === 0 ? (this.remove(e), !0) : super.add(e, n.toString());
        }
        canAdd(e, r) {
            return super.canAdd(e, r) || super.canAdd(e, parseInt(r, 10));
        }
        value(e) {
            return parseInt(super.value(e), 10) || void 0;
        }
    }
    const EE = new wE("indent", "ql-indent", {
        scope: K.BLOCK,
        whitelist: [1, 2, 3, 4, 5, 6, 7, 8],
    });
    class _E extends Ne {
        static blotName = "blockquote";
        static tagName = "blockquote";
    }
    class xE extends Ne {
        static blotName = "header";
        static tagName = ["H1", "H2", "H3", "H4", "H5", "H6"];
        static formats(e) {
            return this.tagName.indexOf(e.tagName) + 1;
        }
    }
    class rs extends Ar {}
    rs.blotName = "list-container";
    rs.tagName = "OL";
    class ns extends Ne {
        static create(e) {
            const r = super.create();
            return (r.setAttribute("data-list", e), r);
        }
        static formats(e) {
            return e.getAttribute("data-list") || void 0;
        }
        static register() {
            L.register(rs);
        }
        constructor(e, r) {
            super(e, r);
            const n = r.ownerDocument.createElement("span"),
                s = (i) => {
                    if (!e.isEnabled()) return;
                    const o = this.statics.formats(r, e);
                    o === "checked"
                        ? (this.format("list", "unchecked"), i.preventDefault())
                        : o === "unchecked" &&
                          (this.format("list", "checked"), i.preventDefault());
                };
            (n.addEventListener("mousedown", s),
                n.addEventListener("touchstart", s),
                this.attachUI(n));
        }
        format(e, r) {
            e === this.statics.blotName && r
                ? this.domNode.setAttribute("data-list", r)
                : super.format(e, r);
        }
    }
    ns.blotName = "list";
    ns.tagName = "LI";
    rs.allowedChildren = [ns];
    ns.requiredContainer = rs;
    class ll extends Pe {
        static blotName = "bold";
        static tagName = ["STRONG", "B"];
        static create() {
            return super.create();
        }
        static formats() {
            return !0;
        }
        optimize(e) {
            (super.optimize(e),
                this.domNode.tagName !== this.statics.tagName[0] &&
                    this.replaceWith(this.statics.blotName));
        }
    }
    class AE extends ll {
        static blotName = "italic";
        static tagName = ["EM", "I"];
    }
    class ti extends Pe {
        static blotName = "link";
        static tagName = "A";
        static SANITIZED_URL = "about:blank";
        static PROTOCOL_WHITELIST = ["http", "https", "mailto", "tel", "sms"];
        static create(e) {
            const r = super.create(e);
            return (
                r.setAttribute("href", this.sanitize(e)),
                r.setAttribute("rel", "noopener noreferrer"),
                r.setAttribute("target", "_blank"),
                r
            );
        }
        static formats(e) {
            return e.getAttribute("href");
        }
        static sanitize(e) {
            return Ru(e, this.PROTOCOL_WHITELIST) ? e : this.SANITIZED_URL;
        }
        format(e, r) {
            e !== this.statics.blotName || !r
                ? super.format(e, r)
                : this.domNode.setAttribute(
                      "href",
                      this.constructor.sanitize(r),
                  );
        }
    }
    function Ru(t, e) {
        const r = document.createElement("a");
        r.href = t;
        const n = r.href.slice(0, r.href.indexOf(":"));
        return e.indexOf(n) > -1;
    }
    class SE extends Pe {
        static blotName = "script";
        static tagName = ["SUB", "SUP"];
        static create(e) {
            return e === "super"
                ? document.createElement("sup")
                : e === "sub"
                  ? document.createElement("sub")
                  : super.create(e);
        }
        static formats(e) {
            if (e.tagName === "SUB") return "sub";
            if (e.tagName === "SUP") return "super";
        }
    }
    class TE extends ll {
        static blotName = "strike";
        static tagName = ["S", "STRIKE"];
    }
    class NE extends Pe {
        static blotName = "underline";
        static tagName = "U";
    }
    class OE extends el {
        static blotName = "formula";
        static className = "ql-formula";
        static tagName = "SPAN";
        static create(e) {
            if (window.katex == null)
                throw new Error("Formula module requires KaTeX.");
            const r = super.create(e);
            return (
                typeof e == "string" &&
                    (window.katex.render(e, r, {
                        throwOnError: !1,
                        errorColor: "#f00",
                    }),
                    r.setAttribute("data-value", e)),
                r
            );
        }
        static value(e) {
            return e.getAttribute("data-value");
        }
        html() {
            const { formula: e } = this.value();
            return `<span>${e}</span>`;
        }
    }
    const U0 = ["alt", "height", "width"];
    class CE extends He {
        static blotName = "image";
        static tagName = "IMG";
        static create(e) {
            const r = super.create(e);
            return (
                typeof e == "string" && r.setAttribute("src", this.sanitize(e)),
                r
            );
        }
        static formats(e) {
            return U0.reduce(
                (r, n) => (e.hasAttribute(n) && (r[n] = e.getAttribute(n)), r),
                {},
            );
        }
        static match(e) {
            return (
                /\.(jpe?g|gif|png)$/.test(e) || /^data:image\/.+;base64/.test(e)
            );
        }
        static sanitize(e) {
            return Ru(e, ["http", "https", "data"]) ? e : "//:0";
        }
        static value(e) {
            return e.getAttribute("src");
        }
        format(e, r) {
            U0.indexOf(e) > -1
                ? r
                    ? this.domNode.setAttribute(e, r)
                    : this.domNode.removeAttribute(e)
                : super.format(e, r);
        }
    }
    const F0 = ["height", "width"];
    class LE extends Ge {
        static blotName = "video";
        static className = "ql-video";
        static tagName = "IFRAME";
        static create(e) {
            const r = super.create(e);
            return (
                r.setAttribute("frameborder", "0"),
                r.setAttribute("allowfullscreen", "true"),
                r.setAttribute("src", this.sanitize(e)),
                r
            );
        }
        static formats(e) {
            return F0.reduce(
                (r, n) => (e.hasAttribute(n) && (r[n] = e.getAttribute(n)), r),
                {},
            );
        }
        static sanitize(e) {
            return ti.sanitize(e);
        }
        static value(e) {
            return e.getAttribute("src");
        }
        format(e, r) {
            F0.indexOf(e) > -1
                ? r
                    ? this.domNode.setAttribute(e, r)
                    : this.domNode.removeAttribute(e)
                : super.format(e, r);
        }
        html() {
            const { video: e } = this.value();
            return `<a href="${e}">${e}</a>`;
        }
    }
    const In = new lt("code-token", "hljs", { scope: K.INLINE });
    class Lt extends Pe {
        static formats(e, r) {
            for (; e != null && e !== r.domNode; ) {
                if (e.classList && e.classList.contains(Be.className))
                    return super.formats(e, r);
                e = e.parentNode;
            }
        }
        constructor(e, r, n) {
            (super(e, r, n), In.add(this.domNode, n));
        }
        format(e, r) {
            e !== Lt.blotName
                ? super.format(e, r)
                : r
                  ? In.add(this.domNode, r)
                  : (In.remove(this.domNode),
                    this.domNode.classList.remove(this.statics.className));
        }
        optimize() {
            (super.optimize(...arguments),
                In.value(this.domNode) || this.unwrap());
        }
    }
    Lt.blotName = "code-token";
    Lt.className = "ql-token";
    class Ve extends Be {
        static create(e) {
            const r = super.create(e);
            return (
                typeof e == "string" && r.setAttribute("data-language", e),
                r
            );
        }
        static formats(e) {
            return e.getAttribute("data-language") || "plain";
        }
        static register() {}
        format(e, r) {
            e === this.statics.blotName && r
                ? this.domNode.setAttribute("data-language", r)
                : super.format(e, r);
        }
        replaceWith(e, r) {
            return (
                this.formatAt(0, this.length(), Lt.blotName, !1),
                super.replaceWith(e, r)
            );
        }
    }
    class $n extends Sr {
        attach() {
            (super.attach(),
                (this.forceNext = !1),
                this.scroll.emitMount(this));
        }
        format(e, r) {
            e === Ve.blotName &&
                ((this.forceNext = !0),
                this.children.forEach((n) => {
                    n.format(e, r);
                }));
        }
        formatAt(e, r, n, s) {
            (n === Ve.blotName && (this.forceNext = !0),
                super.formatAt(e, r, n, s));
        }
        highlight(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : !1;
            if (this.children.head == null) return;
            const s = `${Array.from(this.domNode.childNodes)
                    .filter((o) => o !== this.uiNode)
                    .map((o) => o.textContent).join(`
`)}
`,
                i = Ve.formats(this.children.head.domNode);
            if (r || this.forceNext || this.cachedText !== s) {
                if (s.trim().length > 0 || this.cachedText == null) {
                    const o = this.children.reduce(
                            (l, f) => l.concat(mu(f, !1)),
                            new z(),
                        ),
                        a = e(s, i);
                    o.diff(a).reduce((l, f) => {
                        let { retain: u, attributes: d } = f;
                        return u
                            ? (d &&
                                  Object.keys(d).forEach((h) => {
                                      [Ve.blotName, Lt.blotName].includes(h) &&
                                          this.formatAt(l, u, h, d[h]);
                                  }),
                              l + u)
                            : l;
                    }, 0);
                }
                ((this.cachedText = s), (this.forceNext = !1));
            }
        }
        html(e, r) {
            const [n] = this.children.find(e);
            return `<pre data-language="${n ? Ve.formats(n.domNode) : "plain"}">
${yi(this.code(e, r))}
</pre>`;
        }
        optimize(e) {
            if (
                (super.optimize(e),
                this.parent != null &&
                    this.children.head != null &&
                    this.uiNode != null)
            ) {
                const r = Ve.formats(this.children.head.domNode);
                r !== this.uiNode.value && (this.uiNode.value = r);
            }
        }
    }
    $n.allowedChildren = [Ve];
    Ve.requiredContainer = $n;
    Ve.allowedChildren = [Lt, tt, it, ft];
    const RE = (t, e, r) => {
        if (typeof t.versionString == "string") {
            const n = t.versionString.split(".")[0];
            if (parseInt(n, 10) >= 11)
                return t.highlight(r, { language: e }).value;
        }
        return t.highlight(e, r).value;
    };
    class qu extends wt {
        static register() {
            (L.register(Lt, !0), L.register(Ve, !0), L.register($n, !0));
        }
        constructor(e, r) {
            if ((super(e, r), this.options.hljs == null))
                throw new Error(
                    "Syntax module requires highlight.js. Please include the library on the page before Quill.",
                );
            ((this.languages = this.options.languages.reduce((n, s) => {
                let { key: i } = s;
                return ((n[i] = !0), n);
            }, {})),
                (this.highlightBlot = this.highlightBlot.bind(this)),
                this.initListener(),
                this.initTimer());
        }
        initListener() {
            this.quill.on(L.events.SCROLL_BLOT_MOUNT, (e) => {
                if (!(e instanceof $n)) return;
                const r = this.quill.root.ownerDocument.createElement("select");
                (this.options.languages.forEach((n) => {
                    let { key: s, label: i } = n;
                    const o = r.ownerDocument.createElement("option");
                    ((o.textContent = i),
                        o.setAttribute("value", s),
                        r.appendChild(o));
                }),
                    r.addEventListener("change", () => {
                        (e.format(Ve.blotName, r.value),
                            this.quill.root.focus(),
                            this.highlight(e, !0));
                    }),
                    e.uiNode == null &&
                        (e.attachUI(r),
                        e.children.head &&
                            (r.value = Ve.formats(e.children.head.domNode))));
            });
        }
        initTimer() {
            let e = null;
            this.quill.on(L.events.SCROLL_OPTIMIZE, () => {
                (e && clearTimeout(e),
                    (e = setTimeout(() => {
                        (this.highlight(), (e = null));
                    }, this.options.interval)));
            });
        }
        highlight() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : null,
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : !1;
            if (this.quill.selection.composing) return;
            this.quill.update(L.sources.USER);
            const n = this.quill.getSelection();
            ((e == null ? this.quill.scroll.descendants($n) : [e]).forEach(
                (i) => {
                    i.highlight(this.highlightBlot, r);
                },
            ),
                this.quill.update(L.sources.SILENT),
                n != null && this.quill.setSelection(n, L.sources.SILENT));
        }
        highlightBlot(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : "plain";
            if (((r = this.languages[r] ? r : "plain"), r === "plain"))
                return yi(e)
                    .split(
                        `
`,
                    )
                    .reduce(
                        (s, i, o) => (
                            o !== 0 &&
                                s.insert(
                                    `
`,
                                    { [Be.blotName]: r },
                                ),
                            s.insert(i)
                        ),
                        new z(),
                    );
            const n = this.quill.root.ownerDocument.createElement("div");
            return (
                n.classList.add(Be.className),
                (n.innerHTML = RE(this.options.hljs, r, e)),
                al(
                    this.quill.scroll,
                    n,
                    [
                        (s, i) => {
                            const o = In.value(s);
                            return o
                                ? i.compose(
                                      new z().retain(i.length(), {
                                          [Lt.blotName]: o,
                                      }),
                                  )
                                : i;
                        },
                    ],
                    [
                        (s, i) =>
                            s.data
                                .split(
                                    `
`,
                                )
                                .reduce(
                                    (o, a, l) => (
                                        l !== 0 &&
                                            o.insert(
                                                `
`,
                                                { [Be.blotName]: r },
                                            ),
                                        o.insert(a)
                                    ),
                                    i,
                                ),
                    ],
                    new WeakMap(),
                )
            );
        }
    }
    qu.DEFAULTS = {
        hljs: window.hljs,
        interval: 1e3,
        languages: [
            { key: "plain", label: "Plain" },
            { key: "bash", label: "Bash" },
            { key: "cpp", label: "C++" },
            { key: "cs", label: "C#" },
            { key: "css", label: "CSS" },
            { key: "diff", label: "Diff" },
            { key: "xml", label: "HTML/XML" },
            { key: "java", label: "Java" },
            { key: "javascript", label: "JavaScript" },
            { key: "markdown", label: "Markdown" },
            { key: "php", label: "PHP" },
            { key: "python", label: "Python" },
            { key: "ruby", label: "Ruby" },
            { key: "sql", label: "SQL" },
        ],
    };
    class rt extends Ne {
        static blotName = "table";
        static tagName = "TD";
        static create(e) {
            const r = super.create();
            return (
                e
                    ? r.setAttribute("data-row", e)
                    : r.setAttribute("data-row", fl()),
                r
            );
        }
        static formats(e) {
            if (e.hasAttribute("data-row")) return e.getAttribute("data-row");
        }
        cellOffset() {
            return this.parent ? this.parent.children.indexOf(this) : -1;
        }
        format(e, r) {
            e === rt.blotName && r
                ? this.domNode.setAttribute("data-row", r)
                : super.format(e, r);
        }
        row() {
            return this.parent;
        }
        rowOffset() {
            return this.row() ? this.row().rowOffset() : -1;
        }
        table() {
            return this.row() && this.row().table();
        }
    }
    class vr extends Ar {
        static blotName = "table-row";
        static tagName = "TR";
        checkMerge() {
            if (super.checkMerge() && this.next.children.head != null) {
                const e = this.children.head.formats(),
                    r = this.children.tail.formats(),
                    n = this.next.children.head.formats(),
                    s = this.next.children.tail.formats();
                return (
                    e.table === r.table &&
                    e.table === n.table &&
                    e.table === s.table
                );
            }
            return !1;
        }
        optimize(e) {
            (super.optimize(e),
                this.children.forEach((r) => {
                    if (r.next == null) return;
                    const n = r.formats(),
                        s = r.next.formats();
                    if (n.table !== s.table) {
                        const i = this.splitAfter(r);
                        (i && i.optimize(), this.prev && this.prev.optimize());
                    }
                }));
        }
        rowOffset() {
            return this.parent ? this.parent.children.indexOf(this) : -1;
        }
        table() {
            return this.parent && this.parent.parent;
        }
    }
    class Ut extends Ar {
        static blotName = "table-body";
        static tagName = "TBODY";
    }
    class ri extends Ar {
        static blotName = "table-container";
        static tagName = "TABLE";
        balanceCells() {
            const e = this.descendants(vr),
                r = e.reduce((n, s) => Math.max(s.children.length, n), 0);
            e.forEach((n) => {
                new Array(r - n.children.length).fill(0).forEach(() => {
                    let s;
                    n.children.head != null &&
                        (s = rt.formats(n.children.head.domNode));
                    const i = this.scroll.create(rt.blotName, s);
                    (n.appendChild(i), i.optimize());
                });
            });
        }
        cells(e) {
            return this.rows().map((r) => r.children.at(e));
        }
        deleteColumn(e) {
            const [r] = this.descendant(Ut);
            r == null ||
                r.children.head == null ||
                r.children.forEach((n) => {
                    const s = n.children.at(e);
                    s?.remove();
                });
        }
        insertColumn(e) {
            const [r] = this.descendant(Ut);
            r == null ||
                r.children.head == null ||
                r.children.forEach((n) => {
                    const s = n.children.at(e),
                        i = rt.formats(n.children.head.domNode),
                        o = this.scroll.create(rt.blotName, i);
                    n.insertBefore(o, s);
                });
        }
        insertRow(e) {
            const [r] = this.descendant(Ut);
            if (r == null || r.children.head == null) return;
            const n = fl(),
                s = this.scroll.create(vr.blotName);
            r.children.head.children.forEach(() => {
                const o = this.scroll.create(rt.blotName, n);
                s.appendChild(o);
            });
            const i = r.children.at(e);
            r.insertBefore(s, i);
        }
        rows() {
            const e = this.children.head;
            return e == null ? [] : e.children.map((r) => r);
        }
    }
    ri.allowedChildren = [Ut];
    Ut.requiredContainer = ri;
    Ut.allowedChildren = [vr];
    vr.requiredContainer = Ut;
    vr.allowedChildren = [rt];
    rt.requiredContainer = vr;
    function fl() {
        return `row-${Math.random().toString(36).slice(2, 6)}`;
    }
    class qE extends wt {
        static register() {
            (L.register(rt), L.register(vr), L.register(Ut), L.register(ri));
        }
        constructor() {
            (super(...arguments), this.listenBalanceCells());
        }
        balanceTables() {
            this.quill.scroll.descendants(ri).forEach((e) => {
                e.balanceCells();
            });
        }
        deleteColumn() {
            const [e, , r] = this.getTable();
            r != null &&
                (e.deleteColumn(r.cellOffset()),
                this.quill.update(L.sources.USER));
        }
        deleteRow() {
            const [, e] = this.getTable();
            e != null && (e.remove(), this.quill.update(L.sources.USER));
        }
        deleteTable() {
            const [e] = this.getTable();
            if (e == null) return;
            const r = e.offset();
            (e.remove(),
                this.quill.update(L.sources.USER),
                this.quill.setSelection(r, L.sources.SILENT));
        }
        getTable() {
            let e =
                arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : this.quill.getSelection();
            if (e == null) return [null, null, null, -1];
            const [r, n] = this.quill.getLine(e.index);
            if (r == null || r.statics.blotName !== rt.blotName)
                return [null, null, null, -1];
            const s = r.parent;
            return [s.parent.parent, s, r, n];
        }
        insertColumn(e) {
            const r = this.quill.getSelection();
            if (!r) return;
            const [n, s, i] = this.getTable(r);
            if (i == null) return;
            const o = i.cellOffset();
            (n.insertColumn(o + e), this.quill.update(L.sources.USER));
            let a = s.rowOffset();
            (e === 0 && (a += 1),
                this.quill.setSelection(
                    r.index + a,
                    r.length,
                    L.sources.SILENT,
                ));
        }
        insertColumnLeft() {
            this.insertColumn(0);
        }
        insertColumnRight() {
            this.insertColumn(1);
        }
        insertRow(e) {
            const r = this.quill.getSelection();
            if (!r) return;
            const [n, s, i] = this.getTable(r);
            if (i == null) return;
            const o = s.rowOffset();
            (n.insertRow(o + e),
                this.quill.update(L.sources.USER),
                e > 0
                    ? this.quill.setSelection(r, L.sources.SILENT)
                    : this.quill.setSelection(
                          r.index + s.children.length,
                          r.length,
                          L.sources.SILENT,
                      ));
        }
        insertRowAbove() {
            this.insertRow(0);
        }
        insertRowBelow() {
            this.insertRow(1);
        }
        insertTable(e, r) {
            const n = this.quill.getSelection();
            if (n == null) return;
            const s = new Array(e).fill(0).reduce((i) => {
                const o = new Array(r)
                    .fill(
                        `
`,
                    )
                    .join("");
                return i.insert(o, { table: fl() });
            }, new z().retain(n.index));
            (this.quill.updateContents(s, L.sources.USER),
                this.quill.setSelection(n.index, L.sources.SILENT),
                this.balanceTables());
        }
        listenBalanceCells() {
            this.quill.on(L.events.SCROLL_OPTIMIZE, (e) => {
                e.some((r) =>
                    ["TD", "TR", "TBODY", "TABLE"].includes(r.target.tagName)
                        ? (this.quill.once(L.events.TEXT_CHANGE, (n, s, i) => {
                              i === L.sources.USER && this.balanceTables();
                          }),
                          !0)
                        : !1,
                );
            });
        }
    }
    const H0 = qt("quill:toolbar");
    class cl extends wt {
        constructor(e, r) {
            if ((super(e, r), Array.isArray(this.options.container))) {
                const n = document.createElement("div");
                (n.setAttribute("role", "toolbar"),
                    kE(n, this.options.container),
                    e.container?.parentNode?.insertBefore(n, e.container),
                    (this.container = n));
            } else
                typeof this.options.container == "string"
                    ? (this.container = document.querySelector(
                          this.options.container,
                      ))
                    : (this.container = this.options.container);
            if (!(this.container instanceof HTMLElement)) {
                H0.error("Container required for toolbar", this.options);
                return;
            }
            (this.container.classList.add("ql-toolbar"),
                (this.controls = []),
                (this.handlers = {}),
                this.options.handlers &&
                    Object.keys(this.options.handlers).forEach((n) => {
                        const s = this.options.handlers?.[n];
                        s && this.addHandler(n, s);
                    }),
                Array.from(
                    this.container.querySelectorAll("button, select"),
                ).forEach((n) => {
                    this.attach(n);
                }),
                this.quill.on(L.events.EDITOR_CHANGE, () => {
                    const [n] = this.quill.selection.getRange();
                    this.update(n);
                }));
        }
        addHandler(e, r) {
            this.handlers[e] = r;
        }
        attach(e) {
            let r = Array.from(e.classList).find((s) => s.indexOf("ql-") === 0);
            if (!r) return;
            if (
                ((r = r.slice(3)),
                e.tagName === "BUTTON" && e.setAttribute("type", "button"),
                this.handlers[r] == null && this.quill.scroll.query(r) == null)
            ) {
                H0.warn("ignoring attaching to nonexistent format", r, e);
                return;
            }
            const n = e.tagName === "SELECT" ? "change" : "click";
            (e.addEventListener(n, (s) => {
                let i;
                if (e.tagName === "SELECT") {
                    if (e.selectedIndex < 0) return;
                    const a = e.options[e.selectedIndex];
                    a.hasAttribute("selected") ? (i = !1) : (i = a.value || !1);
                } else
                    (e.classList.contains("ql-active")
                        ? (i = !1)
                        : (i = e.value || !e.hasAttribute("value")),
                        s.preventDefault());
                this.quill.focus();
                const [o] = this.quill.selection.getRange();
                if (this.handlers[r] != null) this.handlers[r].call(this, i);
                else if (this.quill.scroll.query(r).prototype instanceof He) {
                    if (((i = prompt(`Enter ${r}`)), !i)) return;
                    this.quill.updateContents(
                        new z()
                            .retain(o.index)
                            .delete(o.length)
                            .insert({ [r]: i }),
                        L.sources.USER,
                    );
                } else this.quill.format(r, i, L.sources.USER);
                this.update(o);
            }),
                this.controls.push([r, e]));
        }
        update(e) {
            const r = e == null ? {} : this.quill.getFormat(e);
            this.controls.forEach((n) => {
                const [s, i] = n;
                if (i.tagName === "SELECT") {
                    let o = null;
                    if (e == null) o = null;
                    else if (r[s] == null)
                        o = i.querySelector("option[selected]");
                    else if (!Array.isArray(r[s])) {
                        let a = r[s];
                        (typeof a == "string" && (a = a.replace(/"/g, '\\"')),
                            (o = i.querySelector(`option[value="${a}"]`)));
                    }
                    o == null
                        ? ((i.value = ""), (i.selectedIndex = -1))
                        : (o.selected = !0);
                } else if (e == null)
                    (i.classList.remove("ql-active"),
                        i.setAttribute("aria-pressed", "false"));
                else if (i.hasAttribute("value")) {
                    const o = r[s],
                        a =
                            o === i.getAttribute("value") ||
                            (o != null &&
                                o.toString() === i.getAttribute("value")) ||
                            (o == null && !i.getAttribute("value"));
                    (i.classList.toggle("ql-active", a),
                        i.setAttribute("aria-pressed", a.toString()));
                } else {
                    const o = r[s] != null;
                    (i.classList.toggle("ql-active", o),
                        i.setAttribute("aria-pressed", o.toString()));
                }
            });
        }
    }
    cl.DEFAULTS = {};
    function z0(t, e, r) {
        const n = document.createElement("button");
        (n.setAttribute("type", "button"),
            n.classList.add(`ql-${e}`),
            n.setAttribute("aria-pressed", "false"),
            r != null
                ? ((n.value = r), n.setAttribute("aria-label", `${e}: ${r}`))
                : n.setAttribute("aria-label", e),
            t.appendChild(n));
    }
    function kE(t, e) {
        (Array.isArray(e[0]) || (e = [e]),
            e.forEach((r) => {
                const n = document.createElement("span");
                (n.classList.add("ql-formats"),
                    r.forEach((s) => {
                        if (typeof s == "string") z0(n, s);
                        else {
                            const i = Object.keys(s)[0],
                                o = s[i];
                            Array.isArray(o) ? IE(n, i, o) : z0(n, i, o);
                        }
                    }),
                    t.appendChild(n));
            }));
    }
    function IE(t, e, r) {
        const n = document.createElement("select");
        (n.classList.add(`ql-${e}`),
            r.forEach((s) => {
                const i = document.createElement("option");
                (s !== !1
                    ? i.setAttribute("value", String(s))
                    : i.setAttribute("selected", "selected"),
                    n.appendChild(i));
            }),
            t.appendChild(n));
    }
    cl.DEFAULTS = {
        container: null,
        handlers: {
            clean() {
                const t = this.quill.getSelection();
                if (t != null)
                    if (t.length === 0) {
                        const e = this.quill.getFormat();
                        Object.keys(e).forEach((r) => {
                            this.quill.scroll.query(r, K.INLINE) != null &&
                                this.quill.format(r, !1, L.sources.USER);
                        });
                    } else
                        this.quill.removeFormat(
                            t.index,
                            t.length,
                            L.sources.USER,
                        );
            },
            direction(t) {
                const { align: e } = this.quill.getFormat();
                (t === "rtl" && e == null
                    ? this.quill.format("align", "right", L.sources.USER)
                    : !t &&
                      e === "right" &&
                      this.quill.format("align", !1, L.sources.USER),
                    this.quill.format("direction", t, L.sources.USER));
            },
            indent(t) {
                const e = this.quill.getSelection(),
                    r = this.quill.getFormat(e),
                    n = parseInt(r.indent || 0, 10);
                if (t === "+1" || t === "-1") {
                    let s = t === "+1" ? 1 : -1;
                    (r.direction === "rtl" && (s *= -1),
                        this.quill.format("indent", n + s, L.sources.USER));
                }
            },
            link(t) {
                (t === !0 && (t = prompt("Enter link URL:")),
                    this.quill.format("link", t, L.sources.USER));
            },
            list(t) {
                const e = this.quill.getSelection(),
                    r = this.quill.getFormat(e);
                t === "check"
                    ? r.list === "checked" || r.list === "unchecked"
                        ? this.quill.format("list", !1, L.sources.USER)
                        : this.quill.format("list", "unchecked", L.sources.USER)
                    : this.quill.format("list", t, L.sources.USER);
            },
        },
    };
    const PE =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="3" x2="15" y1="9" y2="9"/><line class="ql-stroke" x1="3" x2="13" y1="14" y2="14"/><line class="ql-stroke" x1="3" x2="9" y1="4" y2="4"/></svg>',
        ME =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="15" x2="3" y1="9" y2="9"/><line class="ql-stroke" x1="14" x2="4" y1="14" y2="14"/><line class="ql-stroke" x1="12" x2="6" y1="4" y2="4"/></svg>',
        BE =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="15" x2="3" y1="9" y2="9"/><line class="ql-stroke" x1="15" x2="5" y1="14" y2="14"/><line class="ql-stroke" x1="15" x2="9" y1="4" y2="4"/></svg>',
        $E =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="15" x2="3" y1="9" y2="9"/><line class="ql-stroke" x1="15" x2="3" y1="14" y2="14"/><line class="ql-stroke" x1="15" x2="3" y1="4" y2="4"/></svg>',
        DE =
            '<svg viewbox="0 0 18 18"><g class="ql-fill ql-color-label"><polygon points="6 6.868 6 6 5 6 5 7 5.942 7 6 6.868"/><rect height="1" width="1" x="4" y="4"/><polygon points="6.817 5 6 5 6 6 6.38 6 6.817 5"/><rect height="1" width="1" x="2" y="6"/><rect height="1" width="1" x="3" y="5"/><rect height="1" width="1" x="4" y="7"/><polygon points="4 11.439 4 11 3 11 3 12 3.755 12 4 11.439"/><rect height="1" width="1" x="2" y="12"/><rect height="1" width="1" x="2" y="9"/><rect height="1" width="1" x="2" y="15"/><polygon points="4.63 10 4 10 4 11 4.192 11 4.63 10"/><rect height="1" width="1" x="3" y="8"/><path d="M10.832,4.2L11,4.582V4H10.708A1.948,1.948,0,0,1,10.832,4.2Z"/><path d="M7,4.582L7.168,4.2A1.929,1.929,0,0,1,7.292,4H7V4.582Z"/><path d="M8,13H7.683l-0.351.8a1.933,1.933,0,0,1-.124.2H8V13Z"/><rect height="1" width="1" x="12" y="2"/><rect height="1" width="1" x="11" y="3"/><path d="M9,3H8V3.282A1.985,1.985,0,0,1,9,3Z"/><rect height="1" width="1" x="2" y="3"/><rect height="1" width="1" x="6" y="2"/><rect height="1" width="1" x="3" y="2"/><rect height="1" width="1" x="5" y="3"/><rect height="1" width="1" x="9" y="2"/><rect height="1" width="1" x="15" y="14"/><polygon points="13.447 10.174 13.469 10.225 13.472 10.232 13.808 11 14 11 14 10 13.37 10 13.447 10.174"/><rect height="1" width="1" x="13" y="7"/><rect height="1" width="1" x="15" y="5"/><rect height="1" width="1" x="14" y="6"/><rect height="1" width="1" x="15" y="8"/><rect height="1" width="1" x="14" y="9"/><path d="M3.775,14H3v1H4V14.314A1.97,1.97,0,0,1,3.775,14Z"/><rect height="1" width="1" x="14" y="3"/><polygon points="12 6.868 12 6 11.62 6 12 6.868"/><rect height="1" width="1" x="15" y="2"/><rect height="1" width="1" x="12" y="5"/><rect height="1" width="1" x="13" y="4"/><polygon points="12.933 9 13 9 13 8 12.495 8 12.933 9"/><rect height="1" width="1" x="9" y="14"/><rect height="1" width="1" x="8" y="15"/><path d="M6,14.926V15H7V14.316A1.993,1.993,0,0,1,6,14.926Z"/><rect height="1" width="1" x="5" y="15"/><path d="M10.668,13.8L10.317,13H10v1h0.792A1.947,1.947,0,0,1,10.668,13.8Z"/><rect height="1" width="1" x="11" y="15"/><path d="M14.332,12.2a1.99,1.99,0,0,1,.166.8H15V12H14.245Z"/><rect height="1" width="1" x="14" y="15"/><rect height="1" width="1" x="15" y="11"/></g><polyline class="ql-stroke" points="5.5 13 9 5 12.5 13"/><line class="ql-stroke" x1="11.63" x2="6.38" y1="11" y2="11"/></svg>',
        jE =
            '<svg viewbox="0 0 18 18"><rect class="ql-fill ql-stroke" height="3" width="3" x="4" y="5"/><rect class="ql-fill ql-stroke" height="3" width="3" x="11" y="5"/><path class="ql-even ql-fill ql-stroke" d="M7,8c0,4.031-3,5-3,5"/><path class="ql-even ql-fill ql-stroke" d="M14,8c0,4.031-3,5-3,5"/></svg>',
        UE =
            '<svg viewbox="0 0 18 18"><path class="ql-stroke" d="M5,4H9.5A2.5,2.5,0,0,1,12,6.5v0A2.5,2.5,0,0,1,9.5,9H5A0,0,0,0,1,5,9V4A0,0,0,0,1,5,4Z"/><path class="ql-stroke" d="M5,9h5.5A2.5,2.5,0,0,1,13,11.5v0A2.5,2.5,0,0,1,10.5,14H5a0,0,0,0,1,0,0V9A0,0,0,0,1,5,9Z"/></svg>',
        FE =
            '<svg class="" viewbox="0 0 18 18"><line class="ql-stroke" x1="5" x2="13" y1="3" y2="3"/><line class="ql-stroke" x1="6" x2="9.35" y1="12" y2="3"/><line class="ql-stroke" x1="11" x2="15" y1="11" y2="15"/><line class="ql-stroke" x1="15" x2="11" y1="11" y2="15"/><rect class="ql-fill" height="1" rx="0.5" ry="0.5" width="7" x="2" y="14"/></svg>',
        K0 =
            '<svg viewbox="0 0 18 18"><polyline class="ql-even ql-stroke" points="5 7 3 9 5 11"/><polyline class="ql-even ql-stroke" points="13 7 15 9 13 11"/><line class="ql-stroke" x1="10" x2="8" y1="5" y2="13"/></svg>',
        HE =
            '<svg viewbox="0 0 18 18"><line class="ql-color-label ql-stroke ql-transparent" x1="3" x2="15" y1="15" y2="15"/><polyline class="ql-stroke" points="5.5 11 9 3 12.5 11"/><line class="ql-stroke" x1="11.63" x2="6.38" y1="9" y2="9"/></svg>',
        zE =
            '<svg viewbox="0 0 18 18"><polygon class="ql-stroke ql-fill" points="3 11 5 9 3 7 3 11"/><line class="ql-stroke ql-fill" x1="15" x2="11" y1="4" y2="4"/><path class="ql-fill" d="M11,3a3,3,0,0,0,0,6h1V3H11Z"/><rect class="ql-fill" height="11" width="1" x="11" y="4"/><rect class="ql-fill" height="11" width="1" x="13" y="4"/></svg>',
        KE =
            '<svg viewbox="0 0 18 18"><polygon class="ql-stroke ql-fill" points="15 12 13 10 15 8 15 12"/><line class="ql-stroke ql-fill" x1="9" x2="5" y1="4" y2="4"/><path class="ql-fill" d="M5,3A3,3,0,0,0,5,9H6V3H5Z"/><rect class="ql-fill" height="11" width="1" x="5" y="4"/><rect class="ql-fill" height="11" width="1" x="7" y="4"/></svg>',
        WE =
            '<svg viewbox="0 0 18 18"><path class="ql-fill" d="M11.759,2.482a2.561,2.561,0,0,0-3.53.607A7.656,7.656,0,0,0,6.8,6.2C6.109,9.188,5.275,14.677,4.15,14.927a1.545,1.545,0,0,0-1.3-.933A0.922,0.922,0,0,0,2,15.036S1.954,16,4.119,16s3.091-2.691,3.7-5.553c0.177-.826.36-1.726,0.554-2.6L8.775,6.2c0.381-1.421.807-2.521,1.306-2.676a1.014,1.014,0,0,0,1.02.56A0.966,0.966,0,0,0,11.759,2.482Z"/><rect class="ql-fill" height="1.6" rx="0.8" ry="0.8" width="5" x="5.15" y="6.2"/><path class="ql-fill" d="M13.663,12.027a1.662,1.662,0,0,1,.266-0.276q0.193,0.069.456,0.138a2.1,2.1,0,0,0,.535.069,1.075,1.075,0,0,0,.767-0.3,1.044,1.044,0,0,0,.314-0.8,0.84,0.84,0,0,0-.238-0.619,0.8,0.8,0,0,0-.594-0.239,1.154,1.154,0,0,0-.781.3,4.607,4.607,0,0,0-.781,1q-0.091.15-.218,0.346l-0.246.38c-0.068-.288-0.137-0.582-0.212-0.885-0.459-1.847-2.494-.984-2.941-0.8-0.482.2-.353,0.647-0.094,0.529a0.869,0.869,0,0,1,1.281.585c0.217,0.751.377,1.436,0.527,2.038a5.688,5.688,0,0,1-.362.467,2.69,2.69,0,0,1-.264.271q-0.221-.08-0.471-0.147a2.029,2.029,0,0,0-.522-0.066,1.079,1.079,0,0,0-.768.3A1.058,1.058,0,0,0,9,15.131a0.82,0.82,0,0,0,.832.852,1.134,1.134,0,0,0,.787-0.3,5.11,5.11,0,0,0,.776-0.993q0.141-.219.215-0.34c0.046-.076.122-0.194,0.223-0.346a2.786,2.786,0,0,0,.918,1.726,2.582,2.582,0,0,0,2.376-.185c0.317-.181.212-0.565,0-0.494A0.807,0.807,0,0,1,14.176,15a5.159,5.159,0,0,1-.913-2.446l0,0Q13.487,12.24,13.663,12.027Z"/></svg>',
        VE =
            '<svg viewBox="0 0 18 18"><path class="ql-fill" d="M10,4V14a1,1,0,0,1-2,0V10H3v4a1,1,0,0,1-2,0V4A1,1,0,0,1,3,4V8H8V4a1,1,0,0,1,2,0Zm6.06787,9.209H14.98975V7.59863a.54085.54085,0,0,0-.605-.60547h-.62744a1.01119,1.01119,0,0,0-.748.29688L11.645,8.56641a.5435.5435,0,0,0-.022.8584l.28613.30762a.53861.53861,0,0,0,.84717.0332l.09912-.08789a1.2137,1.2137,0,0,0,.2417-.35254h.02246s-.01123.30859-.01123.60547V13.209H12.041a.54085.54085,0,0,0-.605.60547v.43945a.54085.54085,0,0,0,.605.60547h4.02686a.54085.54085,0,0,0,.605-.60547v-.43945A.54085.54085,0,0,0,16.06787,13.209Z"/></svg>',
        GE =
            '<svg viewBox="0 0 18 18"><path class="ql-fill" d="M16.73975,13.81445v.43945a.54085.54085,0,0,1-.605.60547H11.855a.58392.58392,0,0,1-.64893-.60547V14.0127c0-2.90527,3.39941-3.42187,3.39941-4.55469a.77675.77675,0,0,0-.84717-.78125,1.17684,1.17684,0,0,0-.83594.38477c-.2749.26367-.561.374-.85791.13184l-.4292-.34082c-.30811-.24219-.38525-.51758-.1543-.81445a2.97155,2.97155,0,0,1,2.45361-1.17676,2.45393,2.45393,0,0,1,2.68408,2.40918c0,2.45312-3.1792,2.92676-3.27832,3.93848h2.79443A.54085.54085,0,0,1,16.73975,13.81445ZM9,3A.99974.99974,0,0,0,8,4V8H3V4A1,1,0,0,0,1,4V14a1,1,0,0,0,2,0V10H8v4a1,1,0,0,0,2,0V4A.99974.99974,0,0,0,9,3Z"/></svg>',
        ZE =
            '<svg viewBox="0 0 18 18"><path class="ql-fill" d="M16.65186,12.30664a2.6742,2.6742,0,0,1-2.915,2.68457,3.96592,3.96592,0,0,1-2.25537-.6709.56007.56007,0,0,1-.13232-.83594L11.64648,13c.209-.34082.48389-.36328.82471-.1543a2.32654,2.32654,0,0,0,1.12256.33008c.71484,0,1.12207-.35156,1.12207-.78125,0-.61523-.61621-.86816-1.46338-.86816H13.2085a.65159.65159,0,0,1-.68213-.41895l-.05518-.10937a.67114.67114,0,0,1,.14307-.78125l.71533-.86914a8.55289,8.55289,0,0,1,.68213-.7373V8.58887a3.93913,3.93913,0,0,1-.748.05469H11.9873a.54085.54085,0,0,1-.605-.60547V7.59863a.54085.54085,0,0,1,.605-.60547h3.75146a.53773.53773,0,0,1,.60547.59375v.17676a1.03723,1.03723,0,0,1-.27539.748L14.74854,10.0293A2.31132,2.31132,0,0,1,16.65186,12.30664ZM9,3A.99974.99974,0,0,0,8,4V8H3V4A1,1,0,0,0,1,4V14a1,1,0,0,0,2,0V10H8v4a1,1,0,0,0,2,0V4A.99974.99974,0,0,0,9,3Z"/></svg>',
        XE =
            '<svg viewBox="0 0 18 18"><path class="ql-fill" d="M10,4V14a1,1,0,0,1-2,0V10H3v4a1,1,0,0,1-2,0V4A1,1,0,0,1,3,4V8H8V4a1,1,0,0,1,2,0Zm7.05371,7.96582v.38477c0,.39648-.165.60547-.46191.60547h-.47314v1.29785a.54085.54085,0,0,1-.605.60547h-.69336a.54085.54085,0,0,1-.605-.60547V12.95605H11.333a.5412.5412,0,0,1-.60547-.60547v-.15332a1.199,1.199,0,0,1,.22021-.748l2.56348-4.05957a.7819.7819,0,0,1,.72607-.39648h1.27637a.54085.54085,0,0,1,.605.60547v3.7627h.33008A.54055.54055,0,0,1,17.05371,11.96582ZM14.28125,8.7207h-.022a4.18969,4.18969,0,0,1-.38525.81348l-1.188,1.80469v.02246h1.5293V9.60059A7.04058,7.04058,0,0,1,14.28125,8.7207Z"/></svg>',
        JE =
            '<svg viewBox="0 0 18 18"><path class="ql-fill" d="M16.74023,12.18555a2.75131,2.75131,0,0,1-2.91553,2.80566,3.908,3.908,0,0,1-2.25537-.68164.54809.54809,0,0,1-.13184-.8252L11.73438,13c.209-.34082.48389-.36328.8252-.1543a2.23757,2.23757,0,0,0,1.1001.33008,1.01827,1.01827,0,0,0,1.1001-.96777c0-.61621-.53906-.97949-1.25439-.97949a2.15554,2.15554,0,0,0-.64893.09961,1.15209,1.15209,0,0,1-.814.01074l-.12109-.04395a.64116.64116,0,0,1-.45117-.71484l.231-3.00391a.56666.56666,0,0,1,.62744-.583H15.541a.54085.54085,0,0,1,.605.60547v.43945a.54085.54085,0,0,1-.605.60547H13.41748l-.04395.72559a1.29306,1.29306,0,0,1-.04395.30859h.022a2.39776,2.39776,0,0,1,.57227-.07715A2.53266,2.53266,0,0,1,16.74023,12.18555ZM9,3A.99974.99974,0,0,0,8,4V8H3V4A1,1,0,0,0,1,4V14a1,1,0,0,0,2,0V10H8v4a1,1,0,0,0,2,0V4A.99974.99974,0,0,0,9,3Z"/></svg>',
        YE =
            '<svg viewBox="0 0 18 18"><path class="ql-fill" d="M14.51758,9.64453a1.85627,1.85627,0,0,0-1.24316.38477H13.252a1.73532,1.73532,0,0,1,1.72754-1.4082,2.66491,2.66491,0,0,1,.5498.06641c.35254.05469.57227.01074.70508-.40723l.16406-.5166a.53393.53393,0,0,0-.373-.75977,4.83723,4.83723,0,0,0-1.17773-.14258c-2.43164,0-3.7627,2.17773-3.7627,4.43359,0,2.47559,1.60645,3.69629,3.19043,3.69629A2.70585,2.70585,0,0,0,16.96,12.19727,2.43861,2.43861,0,0,0,14.51758,9.64453Zm-.23047,3.58691c-.67187,0-1.22168-.81445-1.22168-1.45215,0-.47363.30762-.583.72559-.583.96875,0,1.27734.59375,1.27734,1.12207A.82182.82182,0,0,1,14.28711,13.23145ZM10,4V14a1,1,0,0,1-2,0V10H3v4a1,1,0,0,1-2,0V4A1,1,0,0,1,3,4V8H8V4a1,1,0,0,1,2,0Z"/></svg>',
        QE =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="7" x2="13" y1="4" y2="4"/><line class="ql-stroke" x1="5" x2="11" y1="14" y2="14"/><line class="ql-stroke" x1="8" x2="10" y1="14" y2="4"/></svg>',
        e_ =
            '<svg viewbox="0 0 18 18"><rect class="ql-stroke" height="10" width="12" x="3" y="4"/><circle class="ql-fill" cx="6" cy="7" r="1"/><polyline class="ql-even ql-fill" points="5 12 5 11 7 9 8 10 11 7 13 9 13 12 5 12"/></svg>',
        t_ =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="3" x2="15" y1="14" y2="14"/><line class="ql-stroke" x1="3" x2="15" y1="4" y2="4"/><line class="ql-stroke" x1="9" x2="15" y1="9" y2="9"/><polyline class="ql-fill ql-stroke" points="3 7 3 11 5 9 3 7"/></svg>',
        r_ =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="3" x2="15" y1="14" y2="14"/><line class="ql-stroke" x1="3" x2="15" y1="4" y2="4"/><line class="ql-stroke" x1="9" x2="15" y1="9" y2="9"/><polyline class="ql-stroke" points="5 7 5 11 3 9 5 7"/></svg>',
        n_ =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="7" x2="11" y1="7" y2="11"/><path class="ql-even ql-stroke" d="M8.9,4.577a3.476,3.476,0,0,1,.36,4.679A3.476,3.476,0,0,1,4.577,8.9C3.185,7.5,2.035,6.4,4.217,4.217S7.5,3.185,8.9,4.577Z"/><path class="ql-even ql-stroke" d="M13.423,9.1a3.476,3.476,0,0,0-4.679-.36,3.476,3.476,0,0,0,.36,4.679c1.392,1.392,2.5,2.542,4.679.36S14.815,10.5,13.423,9.1Z"/></svg>',
        s_ =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="6" x2="15" y1="4" y2="4"/><line class="ql-stroke" x1="6" x2="15" y1="9" y2="9"/><line class="ql-stroke" x1="6" x2="15" y1="14" y2="14"/><line class="ql-stroke" x1="3" x2="3" y1="4" y2="4"/><line class="ql-stroke" x1="3" x2="3" y1="9" y2="9"/><line class="ql-stroke" x1="3" x2="3" y1="14" y2="14"/></svg>',
        i_ =
            '<svg class="" viewbox="0 0 18 18"><line class="ql-stroke" x1="9" x2="15" y1="4" y2="4"/><polyline class="ql-stroke" points="3 4 4 5 6 3"/><line class="ql-stroke" x1="9" x2="15" y1="14" y2="14"/><polyline class="ql-stroke" points="3 14 4 15 6 13"/><line class="ql-stroke" x1="9" x2="15" y1="9" y2="9"/><polyline class="ql-stroke" points="3 9 4 10 6 8"/></svg>',
        o_ =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke" x1="7" x2="15" y1="4" y2="4"/><line class="ql-stroke" x1="7" x2="15" y1="9" y2="9"/><line class="ql-stroke" x1="7" x2="15" y1="14" y2="14"/><line class="ql-stroke ql-thin" x1="2.5" x2="4.5" y1="5.5" y2="5.5"/><path class="ql-fill" d="M3.5,6A0.5,0.5,0,0,1,3,5.5V3.085l-0.276.138A0.5,0.5,0,0,1,2.053,3c-0.124-.247-0.023-0.324.224-0.447l1-.5A0.5,0.5,0,0,1,4,2.5v3A0.5,0.5,0,0,1,3.5,6Z"/><path class="ql-stroke ql-thin" d="M4.5,10.5h-2c0-.234,1.85-1.076,1.85-2.234A0.959,0.959,0,0,0,2.5,8.156"/><path class="ql-stroke ql-thin" d="M2.5,14.846a0.959,0.959,0,0,0,1.85-.109A0.7,0.7,0,0,0,3.75,14a0.688,0.688,0,0,0,.6-0.736,0.959,0.959,0,0,0-1.85-.109"/></svg>',
        a_ =
            '<svg viewbox="0 0 18 18"><path class="ql-fill" d="M15.5,15H13.861a3.858,3.858,0,0,0,1.914-2.975,1.8,1.8,0,0,0-1.6-1.751A1.921,1.921,0,0,0,12.021,11.7a0.50013,0.50013,0,1,0,.957.291h0a0.914,0.914,0,0,1,1.053-.725,0.81,0.81,0,0,1,.744.762c0,1.076-1.16971,1.86982-1.93971,2.43082A1.45639,1.45639,0,0,0,12,15.5a0.5,0.5,0,0,0,.5.5h3A0.5,0.5,0,0,0,15.5,15Z"/><path class="ql-fill" d="M9.65,5.241a1,1,0,0,0-1.409.108L6,7.964,3.759,5.349A1,1,0,0,0,2.192,6.59178Q2.21541,6.6213,2.241,6.649L4.684,9.5,2.241,12.35A1,1,0,0,0,3.71,13.70722q0.02557-.02768.049-0.05722L6,11.036,8.241,13.65a1,1,0,1,0,1.567-1.24277Q9.78459,12.3777,9.759,12.35L7.316,9.5,9.759,6.651A1,1,0,0,0,9.65,5.241Z"/></svg>',
        l_ =
            '<svg viewbox="0 0 18 18"><path class="ql-fill" d="M15.5,7H13.861a4.015,4.015,0,0,0,1.914-2.975,1.8,1.8,0,0,0-1.6-1.751A1.922,1.922,0,0,0,12.021,3.7a0.5,0.5,0,1,0,.957.291,0.917,0.917,0,0,1,1.053-.725,0.81,0.81,0,0,1,.744.762c0,1.077-1.164,1.925-1.934,2.486A1.423,1.423,0,0,0,12,7.5a0.5,0.5,0,0,0,.5.5h3A0.5,0.5,0,0,0,15.5,7Z"/><path class="ql-fill" d="M9.651,5.241a1,1,0,0,0-1.41.108L6,7.964,3.759,5.349a1,1,0,1,0-1.519,1.3L4.683,9.5,2.241,12.35a1,1,0,1,0,1.519,1.3L6,11.036,8.241,13.65a1,1,0,0,0,1.519-1.3L7.317,9.5,9.759,6.651A1,1,0,0,0,9.651,5.241Z"/></svg>',
        f_ =
            '<svg viewbox="0 0 18 18"><line class="ql-stroke ql-thin" x1="15.5" x2="2.5" y1="8.5" y2="9.5"/><path class="ql-fill" d="M9.007,8C6.542,7.791,6,7.519,6,6.5,6,5.792,7.283,5,9,5c1.571,0,2.765.679,2.969,1.309a1,1,0,0,0,1.9-.617C13.356,4.106,11.354,3,9,3,6.2,3,4,4.538,4,6.5a3.2,3.2,0,0,0,.5,1.843Z"/><path class="ql-fill" d="M8.984,10C11.457,10.208,12,10.479,12,11.5c0,0.708-1.283,1.5-3,1.5-1.571,0-2.765-.679-2.969-1.309a1,1,0,1,0-1.9.617C4.644,13.894,6.646,15,9,15c2.8,0,5-1.538,5-3.5a3.2,3.2,0,0,0-.5-1.843Z"/></svg>',
        c_ =
            '<svg viewbox="0 0 18 18"><rect class="ql-stroke" height="12" width="12" x="3" y="3"/><rect class="ql-fill" height="2" width="3" x="5" y="5"/><rect class="ql-fill" height="2" width="4" x="9" y="5"/><g class="ql-fill ql-transparent"><rect height="2" width="3" x="5" y="8"/><rect height="2" width="4" x="9" y="8"/><rect height="2" width="3" x="5" y="11"/><rect height="2" width="4" x="9" y="11"/></g></svg>',
        u_ =
            '<svg viewbox="0 0 18 18"><path class="ql-stroke" d="M5,3V9a4.012,4.012,0,0,0,4,4H9a4.012,4.012,0,0,0,4-4V3"/><rect class="ql-fill" height="1" rx="0.5" ry="0.5" width="12" x="3" y="15"/></svg>',
        h_ =
            '<svg viewbox="0 0 18 18"><rect class="ql-stroke" height="12" width="12" x="3" y="3"/><rect class="ql-fill" height="12" width="1" x="5" y="3"/><rect class="ql-fill" height="12" width="1" x="12" y="3"/><rect class="ql-fill" height="2" width="8" x="5" y="8"/><rect class="ql-fill" height="1" width="3" x="3" y="5"/><rect class="ql-fill" height="1" width="3" x="3" y="7"/><rect class="ql-fill" height="1" width="3" x="3" y="10"/><rect class="ql-fill" height="1" width="3" x="3" y="12"/><rect class="ql-fill" height="1" width="3" x="12" y="5"/><rect class="ql-fill" height="1" width="3" x="12" y="7"/><rect class="ql-fill" height="1" width="3" x="12" y="10"/><rect class="ql-fill" height="1" width="3" x="12" y="12"/></svg>',
        Gn = {
            align: { "": PE, center: ME, right: BE, justify: $E },
            background: DE,
            blockquote: jE,
            bold: UE,
            clean: FE,
            code: K0,
            "code-block": K0,
            color: HE,
            direction: { "": zE, rtl: KE },
            formula: WE,
            header: { 1: VE, 2: GE, 3: ZE, 4: XE, 5: JE, 6: YE },
            italic: QE,
            image: e_,
            indent: { "+1": t_, "-1": r_ },
            link: n_,
            list: { bullet: s_, check: i_, ordered: o_ },
            script: { sub: a_, super: l_ },
            strike: f_,
            table: c_,
            underline: u_,
            video: h_,
        },
        d_ =
            '<svg viewbox="0 0 18 18"><polygon class="ql-stroke" points="7 11 9 13 11 11 7 11"/><polygon class="ql-stroke" points="7 7 9 5 11 7 7 7"/></svg>';
    let W0 = 0;
    function V0(t, e) {
        t.setAttribute(e, `${t.getAttribute(e) !== "true"}`);
    }
    class wi {
        constructor(e) {
            ((this.select = e),
                (this.container = document.createElement("span")),
                this.buildPicker(),
                (this.select.style.display = "none"),
                this.select.parentNode.insertBefore(
                    this.container,
                    this.select,
                ),
                this.label.addEventListener("mousedown", () => {
                    this.togglePicker();
                }),
                this.label.addEventListener("keydown", (r) => {
                    switch (r.key) {
                        case "Enter":
                            this.togglePicker();
                            break;
                        case "Escape":
                            (this.escape(), r.preventDefault());
                            break;
                    }
                }),
                this.select.addEventListener("change", this.update.bind(this)));
        }
        togglePicker() {
            (this.container.classList.toggle("ql-expanded"),
                V0(this.label, "aria-expanded"),
                V0(this.options, "aria-hidden"));
        }
        buildItem(e) {
            const r = document.createElement("span");
            ((r.tabIndex = "0"),
                r.setAttribute("role", "button"),
                r.classList.add("ql-picker-item"));
            const n = e.getAttribute("value");
            return (
                n && r.setAttribute("data-value", n),
                e.textContent && r.setAttribute("data-label", e.textContent),
                r.addEventListener("click", () => {
                    this.selectItem(r, !0);
                }),
                r.addEventListener("keydown", (s) => {
                    switch (s.key) {
                        case "Enter":
                            (this.selectItem(r, !0), s.preventDefault());
                            break;
                        case "Escape":
                            (this.escape(), s.preventDefault());
                            break;
                    }
                }),
                r
            );
        }
        buildLabel() {
            const e = document.createElement("span");
            return (
                e.classList.add("ql-picker-label"),
                (e.innerHTML = d_),
                (e.tabIndex = "0"),
                e.setAttribute("role", "button"),
                e.setAttribute("aria-expanded", "false"),
                this.container.appendChild(e),
                e
            );
        }
        buildOptions() {
            const e = document.createElement("span");
            (e.classList.add("ql-picker-options"),
                e.setAttribute("aria-hidden", "true"),
                (e.tabIndex = "-1"),
                (e.id = `ql-picker-options-${W0}`),
                (W0 += 1),
                this.label.setAttribute("aria-controls", e.id),
                (this.options = e),
                Array.from(this.select.options).forEach((r) => {
                    const n = this.buildItem(r);
                    (e.appendChild(n), r.selected === !0 && this.selectItem(n));
                }),
                this.container.appendChild(e));
        }
        buildPicker() {
            (Array.from(this.select.attributes).forEach((e) => {
                this.container.setAttribute(e.name, e.value);
            }),
                this.container.classList.add("ql-picker"),
                (this.label = this.buildLabel()),
                this.buildOptions());
        }
        escape() {
            (this.close(), setTimeout(() => this.label.focus(), 1));
        }
        close() {
            (this.container.classList.remove("ql-expanded"),
                this.label.setAttribute("aria-expanded", "false"),
                this.options.setAttribute("aria-hidden", "true"));
        }
        selectItem(e) {
            let r =
                arguments.length > 1 && arguments[1] !== void 0
                    ? arguments[1]
                    : !1;
            const n = this.container.querySelector(".ql-selected");
            e !== n &&
                (n?.classList.remove("ql-selected"),
                e != null &&
                    (e.classList.add("ql-selected"),
                    (this.select.selectedIndex = Array.from(
                        e.parentNode.children,
                    ).indexOf(e)),
                    e.hasAttribute("data-value")
                        ? this.label.setAttribute(
                              "data-value",
                              e.getAttribute("data-value"),
                          )
                        : this.label.removeAttribute("data-value"),
                    e.hasAttribute("data-label")
                        ? this.label.setAttribute(
                              "data-label",
                              e.getAttribute("data-label"),
                          )
                        : this.label.removeAttribute("data-label"),
                    r &&
                        (this.select.dispatchEvent(new Event("change")),
                        this.close())));
        }
        update() {
            let e;
            if (this.select.selectedIndex > -1) {
                const n =
                    this.container.querySelector(".ql-picker-options").children[
                        this.select.selectedIndex
                    ];
                ((e = this.select.options[this.select.selectedIndex]),
                    this.selectItem(n));
            } else this.selectItem(null);
            const r =
                e != null &&
                e !== this.select.querySelector("option[selected]");
            this.label.classList.toggle("ql-active", r);
        }
    }
    class ku extends wi {
        constructor(e, r) {
            (super(e),
                (this.label.innerHTML = r),
                this.container.classList.add("ql-color-picker"),
                Array.from(this.container.querySelectorAll(".ql-picker-item"))
                    .slice(0, 7)
                    .forEach((n) => {
                        n.classList.add("ql-primary");
                    }));
        }
        buildItem(e) {
            const r = super.buildItem(e);
            return (
                (r.style.backgroundColor = e.getAttribute("value") || ""),
                r
            );
        }
        selectItem(e, r) {
            super.selectItem(e, r);
            const n = this.label.querySelector(".ql-color-label"),
                s = (e && e.getAttribute("data-value")) || "";
            n &&
                (n.tagName === "line"
                    ? (n.style.stroke = s)
                    : (n.style.fill = s));
        }
    }
    class Iu extends wi {
        constructor(e, r) {
            (super(e),
                this.container.classList.add("ql-icon-picker"),
                Array.from(
                    this.container.querySelectorAll(".ql-picker-item"),
                ).forEach((n) => {
                    n.innerHTML = r[n.getAttribute("data-value") || ""];
                }),
                (this.defaultItem =
                    this.container.querySelector(".ql-selected")),
                this.selectItem(this.defaultItem));
        }
        selectItem(e, r) {
            super.selectItem(e, r);
            const n = e || this.defaultItem;
            if (n != null) {
                if (this.label.innerHTML === n.innerHTML) return;
                this.label.innerHTML = n.innerHTML;
            }
        }
    }
    const p_ = (t) => {
        const { overflowY: e } = getComputedStyle(t, null);
        return e !== "visible" && e !== "clip";
    };
    class Pu {
        constructor(e, r) {
            ((this.quill = e),
                (this.boundsContainer = r || document.body),
                (this.root = e.addContainer("ql-tooltip")),
                (this.root.innerHTML = this.constructor.TEMPLATE),
                p_(this.quill.root) &&
                    this.quill.root.addEventListener("scroll", () => {
                        this.root.style.marginTop = `${-1 * this.quill.root.scrollTop}px`;
                    }),
                this.hide());
        }
        hide() {
            this.root.classList.add("ql-hidden");
        }
        position(e) {
            const r = e.left + e.width / 2 - this.root.offsetWidth / 2,
                n = e.bottom + this.quill.root.scrollTop;
            ((this.root.style.left = `${r}px`),
                (this.root.style.top = `${n}px`),
                this.root.classList.remove("ql-flip"));
            const s = this.boundsContainer.getBoundingClientRect(),
                i = this.root.getBoundingClientRect();
            let o = 0;
            if (
                (i.right > s.right &&
                    ((o = s.right - i.right),
                    (this.root.style.left = `${r + o}px`)),
                i.left < s.left &&
                    ((o = s.left - i.left),
                    (this.root.style.left = `${r + o}px`)),
                i.bottom > s.bottom)
            ) {
                const a = i.bottom - i.top,
                    l = e.bottom - e.top + a;
                ((this.root.style.top = `${n - l}px`),
                    this.root.classList.add("ql-flip"));
            }
            return o;
        }
        show() {
            (this.root.classList.remove("ql-editing"),
                this.root.classList.remove("ql-hidden"));
        }
    }
    const g_ = [!1, "center", "right", "justify"],
        m_ = [
            "#000000",
            "#e60000",
            "#ff9900",
            "#ffff00",
            "#008a00",
            "#0066cc",
            "#9933ff",
            "#ffffff",
            "#facccc",
            "#ffebcc",
            "#ffffcc",
            "#cce8cc",
            "#cce0f5",
            "#ebd6ff",
            "#bbbbbb",
            "#f06666",
            "#ffc266",
            "#ffff66",
            "#66b966",
            "#66a3e0",
            "#c285ff",
            "#888888",
            "#a10000",
            "#b26b00",
            "#b2b200",
            "#006100",
            "#0047b2",
            "#6b24b2",
            "#444444",
            "#5c0000",
            "#663d00",
            "#666600",
            "#003700",
            "#002966",
            "#3d1466",
        ],
        b_ = [!1, "serif", "monospace"],
        y_ = ["1", "2", "3", !1],
        v_ = ["small", !1, "large", "huge"];
    class ss extends ln {
        constructor(e, r) {
            super(e, r);
            const n = (s) => {
                if (!document.body.contains(e.root)) {
                    document.body.removeEventListener("click", n);
                    return;
                }
                (this.tooltip != null &&
                    !this.tooltip.root.contains(s.target) &&
                    document.activeElement !== this.tooltip.textbox &&
                    !this.quill.hasFocus() &&
                    this.tooltip.hide(),
                    this.pickers != null &&
                        this.pickers.forEach((i) => {
                            i.container.contains(s.target) || i.close();
                        }));
            };
            e.emitter.listenDOM("click", document.body, n);
        }
        addModule(e) {
            const r = super.addModule(e);
            return (e === "toolbar" && this.extendToolbar(r), r);
        }
        buildButtons(e, r) {
            Array.from(e).forEach((n) => {
                (n.getAttribute("class") || "").split(/\s+/).forEach((i) => {
                    if (i.startsWith("ql-") && ((i = i.slice(3)), r[i] != null))
                        if (i === "direction")
                            n.innerHTML = r[i][""] + r[i].rtl;
                        else if (typeof r[i] == "string") n.innerHTML = r[i];
                        else {
                            const o = n.value || "";
                            o != null && r[i][o] && (n.innerHTML = r[i][o]);
                        }
                });
            });
        }
        buildPickers(e, r) {
            this.pickers = Array.from(e).map((s) => {
                if (
                    s.classList.contains("ql-align") &&
                    (s.querySelector("option") == null && Cn(s, g_),
                    typeof r.align == "object")
                )
                    return new Iu(s, r.align);
                if (
                    s.classList.contains("ql-background") ||
                    s.classList.contains("ql-color")
                ) {
                    const i = s.classList.contains("ql-background")
                        ? "background"
                        : "color";
                    return (
                        s.querySelector("option") == null &&
                            Cn(
                                s,
                                m_,
                                i === "background" ? "#ffffff" : "#000000",
                            ),
                        new ku(s, r[i])
                    );
                }
                return (
                    s.querySelector("option") == null &&
                        (s.classList.contains("ql-font")
                            ? Cn(s, b_)
                            : s.classList.contains("ql-header")
                              ? Cn(s, y_)
                              : s.classList.contains("ql-size") && Cn(s, v_)),
                    new wi(s)
                );
            });
            const n = () => {
                this.pickers.forEach((s) => {
                    s.update();
                });
            };
            this.quill.on(H.events.EDITOR_CHANGE, n);
        }
    }
    ss.DEFAULTS = jt({}, ln.DEFAULTS, {
        modules: {
            toolbar: {
                handlers: {
                    formula() {
                        this.quill.theme.tooltip.edit("formula");
                    },
                    image() {
                        let t = this.container.querySelector(
                            "input.ql-image[type=file]",
                        );
                        (t == null &&
                            ((t = document.createElement("input")),
                            t.setAttribute("type", "file"),
                            t.setAttribute(
                                "accept",
                                this.quill.uploader.options.mimetypes.join(
                                    ", ",
                                ),
                            ),
                            t.classList.add("ql-image"),
                            t.addEventListener("change", () => {
                                const e = this.quill.getSelection(!0);
                                (this.quill.uploader.upload(e, t.files),
                                    (t.value = ""));
                            }),
                            this.container.appendChild(t)),
                            t.click());
                    },
                    video() {
                        this.quill.theme.tooltip.edit("video");
                    },
                },
            },
        },
    });
    class Mu extends Pu {
        constructor(e, r) {
            (super(e, r),
                (this.textbox = this.root.querySelector('input[type="text"]')),
                this.listen());
        }
        listen() {
            this.textbox.addEventListener("keydown", (e) => {
                e.key === "Enter"
                    ? (this.save(), e.preventDefault())
                    : e.key === "Escape" && (this.cancel(), e.preventDefault());
            });
        }
        cancel() {
            (this.hide(), this.restoreFocus());
        }
        edit() {
            let e =
                    arguments.length > 0 && arguments[0] !== void 0
                        ? arguments[0]
                        : "link",
                r =
                    arguments.length > 1 && arguments[1] !== void 0
                        ? arguments[1]
                        : null;
            if (
                (this.root.classList.remove("ql-hidden"),
                this.root.classList.add("ql-editing"),
                this.textbox == null)
            )
                return;
            r != null
                ? (this.textbox.value = r)
                : e !== this.root.getAttribute("data-mode") &&
                  (this.textbox.value = "");
            const n = this.quill.getBounds(this.quill.selection.savedRange);
            (n != null && this.position(n),
                this.textbox.select(),
                this.textbox.setAttribute(
                    "placeholder",
                    this.textbox.getAttribute(`data-${e}`) || "",
                ),
                this.root.setAttribute("data-mode", e));
        }
        restoreFocus() {
            this.quill.focus({ preventScroll: !0 });
        }
        save() {
            let { value: e } = this.textbox;
            switch (this.root.getAttribute("data-mode")) {
                case "link": {
                    const { scrollTop: r } = this.quill.root;
                    (this.linkRange
                        ? (this.quill.formatText(
                              this.linkRange,
                              "link",
                              e,
                              H.sources.USER,
                          ),
                          delete this.linkRange)
                        : (this.restoreFocus(),
                          this.quill.format("link", e, H.sources.USER)),
                        (this.quill.root.scrollTop = r));
                    break;
                }
                case "video":
                    e = w_(e);
                case "formula": {
                    if (!e) break;
                    const r = this.quill.getSelection(!0);
                    if (r != null) {
                        const n = r.index + r.length;
                        (this.quill.insertEmbed(
                            n,
                            this.root.getAttribute("data-mode"),
                            e,
                            H.sources.USER,
                        ),
                            this.root.getAttribute("data-mode") === "formula" &&
                                this.quill.insertText(
                                    n + 1,
                                    " ",
                                    H.sources.USER,
                                ),
                            this.quill.setSelection(n + 2, H.sources.USER));
                    }
                    break;
                }
            }
            ((this.textbox.value = ""), this.hide());
        }
    }
    function w_(t) {
        let e =
            t.match(
                /^(?:(https?):\/\/)?(?:(?:www|m)\.)?youtube\.com\/watch.*v=([a-zA-Z0-9_-]+)/,
            ) ||
            t.match(
                /^(?:(https?):\/\/)?(?:(?:www|m)\.)?youtu\.be\/([a-zA-Z0-9_-]+)/,
            );
        return e
            ? `${e[1] || "https"}://www.youtube.com/embed/${e[2]}?showinfo=0`
            : (e = t.match(/^(?:(https?):\/\/)?(?:www\.)?vimeo\.com\/(\d+)/))
              ? `${e[1] || "https"}://player.vimeo.com/video/${e[2]}/`
              : t;
    }
    function Cn(t, e) {
        let r =
            arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : !1;
        e.forEach((n) => {
            const s = document.createElement("option");
            (n === r
                ? s.setAttribute("selected", "selected")
                : s.setAttribute("value", String(n)),
                t.appendChild(s));
        });
    }
    const E_ = [
        ["bold", "italic", "link"],
        [{ header: 1 }, { header: 2 }, "blockquote"],
    ];
    class __ extends Mu {
        static TEMPLATE = [
            '<span class="ql-tooltip-arrow"></span>',
            '<div class="ql-tooltip-editor">',
            '<input type="text" data-formula="e=mc^2" data-link="https://quilljs.com" data-video="Embed URL">',
            '<a class="ql-close"></a>',
            "</div>",
        ].join("");
        constructor(e, r) {
            (super(e, r),
                this.quill.on(H.events.EDITOR_CHANGE, (n, s, i, o) => {
                    if (n === H.events.SELECTION_CHANGE)
                        if (s != null && s.length > 0 && o === H.sources.USER) {
                            (this.show(),
                                (this.root.style.left = "0px"),
                                (this.root.style.width = ""),
                                (this.root.style.width = `${this.root.offsetWidth}px`));
                            const a = this.quill.getLines(s.index, s.length);
                            if (a.length === 1) {
                                const l = this.quill.getBounds(s);
                                l != null && this.position(l);
                            } else {
                                const l = a[a.length - 1],
                                    f = this.quill.getIndex(l),
                                    u = Math.min(
                                        l.length() - 1,
                                        s.index + s.length - f,
                                    ),
                                    d = this.quill.getBounds(new yr(f, u));
                                d != null && this.position(d);
                            }
                        } else
                            document.activeElement !== this.textbox &&
                                this.quill.hasFocus() &&
                                this.hide();
                }));
        }
        listen() {
            (super.listen(),
                this.root
                    .querySelector(".ql-close")
                    .addEventListener("click", () => {
                        this.root.classList.remove("ql-editing");
                    }),
                this.quill.on(H.events.SCROLL_OPTIMIZE, () => {
                    setTimeout(() => {
                        if (this.root.classList.contains("ql-hidden")) return;
                        const e = this.quill.getSelection();
                        if (e != null) {
                            const r = this.quill.getBounds(e);
                            r != null && this.position(r);
                        }
                    }, 1);
                }));
        }
        cancel() {
            this.show();
        }
        position(e) {
            const r = super.position(e),
                n = this.root.querySelector(".ql-tooltip-arrow");
            return (
                (n.style.marginLeft = ""),
                r !== 0 &&
                    (n.style.marginLeft = `${-1 * r - n.offsetWidth / 2}px`),
                r
            );
        }
    }
    class Bu extends ss {
        constructor(e, r) {
            (r.modules.toolbar != null &&
                r.modules.toolbar.container == null &&
                (r.modules.toolbar.container = E_),
                super(e, r),
                this.quill.container.classList.add("ql-bubble"));
        }
        extendToolbar(e) {
            ((this.tooltip = new __(this.quill, this.options.bounds)),
                e.container != null &&
                    (this.tooltip.root.appendChild(e.container),
                    this.buildButtons(
                        e.container.querySelectorAll("button"),
                        Gn,
                    ),
                    this.buildPickers(
                        e.container.querySelectorAll("select"),
                        Gn,
                    )));
        }
    }
    Bu.DEFAULTS = jt({}, ss.DEFAULTS, {
        modules: {
            toolbar: {
                handlers: {
                    link(t) {
                        t
                            ? this.quill.theme.tooltip.edit()
                            : this.quill.format("link", !1, L.sources.USER);
                    },
                },
            },
        },
    });
    const x_ = [
        [{ header: ["1", "2", "3", !1] }],
        ["bold", "italic", "underline", "link"],
        [{ list: "ordered" }, { list: "bullet" }],
        ["clean"],
    ];
    class A_ extends Mu {
        static TEMPLATE = [
            '<a class="ql-preview" rel="noopener noreferrer" target="_blank" href="about:blank"></a>',
            '<input type="text" data-formula="e=mc^2" data-link="https://quilljs.com" data-video="Embed URL">',
            '<a class="ql-action"></a>',
            '<a class="ql-remove"></a>',
        ].join("");
        preview = this.root.querySelector("a.ql-preview");
        listen() {
            (super.listen(),
                this.root
                    .querySelector("a.ql-action")
                    .addEventListener("click", (e) => {
                        (this.root.classList.contains("ql-editing")
                            ? this.save()
                            : this.edit("link", this.preview.textContent),
                            e.preventDefault());
                    }),
                this.root
                    .querySelector("a.ql-remove")
                    .addEventListener("click", (e) => {
                        if (this.linkRange != null) {
                            const r = this.linkRange;
                            (this.restoreFocus(),
                                this.quill.formatText(
                                    r,
                                    "link",
                                    !1,
                                    H.sources.USER,
                                ),
                                delete this.linkRange);
                        }
                        (e.preventDefault(), this.hide());
                    }),
                this.quill.on(H.events.SELECTION_CHANGE, (e, r, n) => {
                    if (e != null) {
                        if (e.length === 0 && n === H.sources.USER) {
                            const [s, i] = this.quill.scroll.descendant(
                                ti,
                                e.index,
                            );
                            if (s != null) {
                                this.linkRange = new yr(
                                    e.index - i,
                                    s.length(),
                                );
                                const o = ti.formats(s.domNode);
                                ((this.preview.textContent = o),
                                    this.preview.setAttribute("href", o),
                                    this.show());
                                const a = this.quill.getBounds(this.linkRange);
                                a != null && this.position(a);
                                return;
                            }
                        } else delete this.linkRange;
                        this.hide();
                    }
                }));
        }
        show() {
            (super.show(), this.root.removeAttribute("data-mode"));
        }
    }
    class $u extends ss {
        constructor(e, r) {
            (r.modules.toolbar != null &&
                r.modules.toolbar.container == null &&
                (r.modules.toolbar.container = x_),
                super(e, r),
                this.quill.container.classList.add("ql-snow"));
        }
        extendToolbar(e) {
            e.container != null &&
                (e.container.classList.add("ql-snow"),
                this.buildButtons(e.container.querySelectorAll("button"), Gn),
                this.buildPickers(e.container.querySelectorAll("select"), Gn),
                (this.tooltip = new A_(this.quill, this.options.bounds)),
                e.container.querySelector(".ql-link") &&
                    this.quill.keyboard.addBinding(
                        { key: "k", shortKey: !0 },
                        (r, n) => {
                            e.handlers.link.call(e, !n.format.link);
                        },
                    ));
        }
    }
    $u.DEFAULTS = jt({}, ss.DEFAULTS, {
        modules: {
            toolbar: {
                handlers: {
                    link(t) {
                        if (t) {
                            const e = this.quill.getSelection();
                            if (e == null || e.length === 0) return;
                            let r = this.quill.getText(e);
                            /^\S+@\S+\.\S+$/.test(r) &&
                                r.indexOf("mailto:") !== 0 &&
                                (r = `mailto:${r}`);
                            const { tooltip: n } = this.quill.theme;
                            n.edit("link", r);
                        } else this.quill.format("link", !1, L.sources.USER);
                    },
                },
            },
        },
    });
    L.register(
        {
            "attributors/attribute/direction": Eu,
            "attributors/class/align": yu,
            "attributors/class/background": Cw,
            "attributors/class/color": Ow,
            "attributors/class/direction": _u,
            "attributors/class/font": Su,
            "attributors/class/size": Nu,
            "attributors/style/align": vu,
            "attributors/style/background": nl,
            "attributors/style/color": rl,
            "attributors/style/direction": xu,
            "attributors/style/font": Tu,
            "attributors/style/size": Ou,
        },
        !0,
    );
    L.register(
        {
            "formats/align": yu,
            "formats/direction": _u,
            "formats/indent": EE,
            "formats/background": nl,
            "formats/color": rl,
            "formats/font": Su,
            "formats/size": Nu,
            "formats/blockquote": _E,
            "formats/code-block": Be,
            "formats/header": xE,
            "formats/list": ns,
            "formats/bold": ll,
            "formats/code": sl,
            "formats/italic": AE,
            "formats/link": ti,
            "formats/script": SE,
            "formats/strike": TE,
            "formats/underline": NE,
            "formats/formula": OE,
            "formats/image": CE,
            "formats/video": LE,
            "modules/syntax": qu,
            "modules/table": qE,
            "modules/toolbar": cl,
            "themes/bubble": Bu,
            "themes/snow": $u,
            "ui/icons": Gn,
            "ui/picker": wi,
            "ui/icon-picker": Iu,
            "ui/color-picker": ku,
            "ui/tooltip": Pu,
        },
        !0,
    );
    const S_ = [
            "header",
            "bold",
            "italic",
            "underline",
            "strike",
            "list",
            "blockquote",
            "code",
            "link",
        ],
        T_ = [
            [{ header: [1, 2, 3, !1] }],
            ["bold", "italic", "underline", "strike"],
            [{ list: "ordered" }, { list: "bullet" }],
            ["blockquote", "code"],
            ["link"],
            ["clean"],
        ];
    function N_(t) {
        t.data(
            "jobForm",
            ({ maxLength: e = 1e5, storageKey: r = "job-form" } = {}) => {
                let n = null,
                    s = null,
                    i = null;
                return {
                    count: 0,
                    submitting: !1,
                    dirty: !1,
                    init() {
                        const o = this.$refs.input,
                            a = this.$refs.editorShell;
                        try {
                            n = new L(a.querySelector("[data-job-editor]"), {
                                theme: "snow",
                                placeholder:
                                    "Describe the role, responsibilities, and requirements…",
                                formats: S_,
                                modules: { toolbar: T_ },
                            });
                            let l = o.value;
                            try {
                                const f = window.localStorage.getItem(r);
                                f && f.trim() && (!l || !l.trim()) && (l = f);
                            } catch {}
                            (l &&
                                l.trim() &&
                                n.clipboard.dangerouslyPasteHTML(l),
                                this.sync(),
                                (s = () => {
                                    (n.getLength() > e &&
                                        n.deleteText(e, n.getLength()),
                                        (this.dirty = !0),
                                        this.sync());
                                }),
                                n.on("text-change", s),
                                (i = (f) => {
                                    this.dirty &&
                                        !this.submitting &&
                                        (f.preventDefault(),
                                        (f.returnValue = ""));
                                }),
                                window.addEventListener("beforeunload", i),
                                (o.style.display = "none"));
                        } catch (l) {
                            (this.destroy(),
                                (a.hidden = !0),
                                (o.style.display = ""),
                                console.error(
                                    "Unable to initialize the job description editor.",
                                    l,
                                ));
                        }
                    },
                    sync() {
                        const o =
                            n.getText().trim() === "" ? "" : n.root.innerHTML;
                        ((this.$refs.input.value = o),
                            (this.count = Math.max(0, n.getLength() - 1)));
                        try {
                            window.localStorage.setItem(r, o);
                        } catch {}
                    },
                    allowSubmit() {
                        this.submitting = !0;
                        try {
                            window.localStorage.removeItem(r);
                        } catch {}
                    },
                    destroy() {
                        (n && s && n.off("text-change", s),
                            i && window.removeEventListener("beforeunload", i),
                            (n = null));
                    },
                };
            },
        );
    }
    function O_(t) {
        t.data("toastNotification", ({ duration: e = 6500 } = {}) => {
            let r = null,
                n = 0,
                s = e;
            return {
                open: !0,
                hovered: !1,
                focused: !1,
                init() {
                    this.resume();
                },
                pause() {
                    r !== null &&
                        (clearTimeout(r),
                        (r = null),
                        (s = Math.max(0, s - (performance.now() - n))));
                },
                resume() {
                    r !== null ||
                        !this.open ||
                        this.hovered ||
                        this.focused ||
                        e === 0 ||
                        ((n = performance.now()),
                        (r = setTimeout(() => {
                            ((r = null), this.dismiss());
                        }, s)));
                },
                dismiss() {
                    (r !== null && clearTimeout(r),
                        (r = null),
                        (this.open = !1));
                },
                destroy() {
                    (r !== null && clearTimeout(r), (r = null));
                },
            };
        });
    }
    function C_(t) {
        t.data("avatarUpload", ({ savedUrl: e = null } = {}) => {
            let r = null;
            const n = () => {
                (r && URL.revokeObjectURL(r), (r = null));
            };
            return {
                avatarUrl: e,
                avatarFailed: !1,
                avatarFilename: "",
                previewAvatar(s) {
                    n();
                    const i = s.target.files?.[0];
                    ((this.avatarFailed = !1),
                        (this.avatarFilename = i?.name || ""),
                        (r = i ? URL.createObjectURL(i) : null),
                        (this.avatarUrl = r || e));
                },
                destroy() {
                    n();
                },
            };
        });
    }
    const rr = ["overview", "interviews", "activity"],
        L_ = new Set(["application/pdf", "image/jpeg", "image/png"]);
    function R_(
        { documents: t = [], tab: e = "overview" } = {},
        {
            loadPdf: r = async (n) =>
                (
                    await pa(
                        async () => {
                            const { createPdfPreview: s } =
                                await import("./candidate-pdf-Bk76uDWt.js");
                            return { createPdfPreview: s };
                        },
                        __vite__mapDeps([0, 1]),
                    )
                ).createPdfPreview(n),
        } = {},
    ) {
        let n = null,
            s = 0,
            i = !1,
            o = null,
            a = 0,
            l = Promise.resolve();
        return {
            reviewTab: rr.includes(e) ? e : "overview",
            reviewDocuments: t,
            documentId: t[0]?.id || "",
            documentState: "empty",
            documentError: "",
            documentUrl: "",
            documentMime: "",
            pdfPage: 1,
            pdfPages: 0,
            pdfZoom: 1,
            pdfText: "",
            pdfBusy: !1,
            get selectedDocument() {
                return this.reviewDocuments.find(
                    (f) => String(f.id) === String(this.documentId),
                );
            },
            init() {
                const f = this.$el?.dataset;
                if (
                    (rr.includes(f?.restoreReviewTab) &&
                        (this.reviewTab = f.restoreReviewTab),
                    this.reviewDocuments.some(
                        (u) => String(u.id) === f?.restoreDocument,
                    ) && (this.documentId = f.restoreDocument),
                    this.documentId)
                )
                    return this.loadDocument();
            },
            destroy() {
                ((i = !0), (s += 1), n?.abort(), this.releaseDocument());
            },
            selectReviewTab(f, u = !1) {
                rr.includes(f) &&
                    ((this.reviewTab = f),
                    u &&
                        this.$nextTick(() =>
                            this.$refs[`reviewTab${f}`]?.focus(),
                        ));
            },
            moveReviewTab(f) {
                this.selectReviewTab(
                    rr[
                        (rr.indexOf(this.reviewTab) + f + rr.length) % rr.length
                    ],
                    !0,
                );
            },
            openSchedule() {
                (this.selectReviewTab("interviews"),
                    this.$nextTick(() => {
                        const f = this.$refs.scheduleInterview;
                        f &&
                            ((f.open = !0),
                            f.querySelector("summary")?.focus());
                    }));
            },
            selectDocument(f) {
                if (String(f) !== String(this.documentId))
                    return ((this.documentId = String(f)), this.loadDocument());
            },
            releaseDocument() {
                ((a += 1),
                    o &&
                        (l = Promise.allSettled([l, o.destroy()]).then(
                            () => {},
                        )),
                    (o = null),
                    this.documentUrl && URL.revokeObjectURL(this.documentUrl),
                    (this.documentUrl = ""),
                    (this.documentMime = ""),
                    (this.pdfPage = 1),
                    (this.pdfPages = 0),
                    (this.pdfZoom = 1),
                    (this.pdfText = ""),
                    (this.pdfBusy = !1));
            },
            async loadDocument() {
                const f = ++s;
                (n?.abort(),
                    (n = null),
                    this.releaseDocument(),
                    (this.documentError = ""));
                const u = this.selectedDocument;
                if (!u) {
                    this.documentState = "empty";
                    return;
                }
                if (!u.available) {
                    this.documentState = "unavailable";
                    return;
                }
                if (!u.previewable) {
                    this.documentState = "unsupported";
                    return;
                }
                this.documentState = "loading";
                const d = new AbortController();
                n = d;
                try {
                    const h = await fetch(u.preview_url, {
                        signal: d.signal,
                        credentials: "same-origin",
                        cache: "no-store",
                        headers: {
                            Accept: "application/pdf, image/jpeg, image/png",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    });
                    if (i || f !== s) return;
                    if (h.redirected)
                        throw new Error(
                            "Your session has expired. Sign in again to view this document.",
                        );
                    if (!h.ok) {
                        const b = {
                            401: "Your session has expired. Sign in again to view this document.",
                            403: "You do not have permission to preview this document.",
                            404: "This document is no longer available.",
                            415: "This file cannot be previewed. Use the download option to review it.",
                            429: "Too many preview requests. Wait a moment and try again.",
                        };
                        throw new Error(
                            b[h.status] ||
                                "The document could not be loaded. Please try again.",
                        );
                    }
                    const g = (h.headers.get("Content-Type") || "")
                        .split(";")[0]
                        .trim()
                        .toLowerCase();
                    if (!L_.has(g))
                        throw new Error(
                            "This file cannot be displayed safely. Use the download option to review it.",
                        );
                    const v = await h.blob();
                    if (i || f !== s) return;
                    if (!v.size)
                        throw new Error(
                            "This document is empty. Please contact the candidate for a replacement.",
                        );
                    if (((this.documentMime = g), g === "application/pdf")) {
                        let b;
                        try {
                            b = await r(await v.arrayBuffer());
                        } catch {
                            throw new Error(
                                "This PDF could not be opened. It may be password-protected or damaged. Use Download to review it.",
                            );
                        }
                        if (i || f !== s) {
                            await b.destroy();
                            return;
                        }
                        ((o = b),
                            (this.pdfPages = b.numPages),
                            (this.documentState = "ready"),
                            await this.renderPdfPage());
                    } else
                        ((this.documentUrl = URL.createObjectURL(v)),
                            (this.documentState = "ready"));
                } catch (h) {
                    if (i || f !== s || h.name === "AbortError") return;
                    ((this.documentState = "error"),
                        (this.documentError =
                            h instanceof TypeError
                                ? "The document could not be loaded. Check your connection and try again."
                                : h.message));
                } finally {
                    f === s && (n = null);
                }
            },
            documentRenderFailed() {
                (this.releaseDocument(),
                    (this.documentState = "error"),
                    (this.documentError =
                        "Your browser could not display this document. Use the download option to review it."));
            },
            async renderPdfPage() {
                if (!o || i) return;
                const f = o,
                    u = ++a;
                this.pdfBusy = !0;
                try {
                    if (
                        (await this.$nextTick(),
                        await l,
                        f !== o || i || u !== a)
                    )
                        return;
                    const d = await f.render(
                        this.$refs.pdfCanvas,
                        this.$refs.pdfContainer,
                        this.pdfPage,
                        { zoom: this.pdfZoom },
                    );
                    if (f !== o || i || u !== a) return;
                    this.pdfText = d.pageText;
                } catch {
                    if (f !== o || i || u !== a) return;
                    ((this.documentState = "error"),
                        (this.documentError =
                            "This PDF page could not be displayed. Try again or use Download to review it."));
                } finally {
                    u === a && (this.pdfBusy = !1);
                }
            },
            changePdfPage(f) {
                const u = this.pdfPage + f;
                if (!(this.pdfBusy || u < 1 || u > this.pdfPages))
                    return ((this.pdfPage = u), this.renderPdfPage());
            },
            zoomPdf(f) {
                if (!this.pdfBusy)
                    return (
                        (this.pdfZoom =
                            f === 0
                                ? 1
                                : Math.max(0.5, Math.min(2, this.pdfZoom + f))),
                        this.renderPdfPage()
                    );
            },
        };
    }
    function q_(t) {
        t.data("candidateReview", (e) => R_(e));
    }
    const k_ = new Set(["interview_online", "interview_onsite"]);
    function I_({
        mode: t = "online",
        context: e = "schedule",
        templates: r = [],
        subject: n = "",
        body: s = "",
        modeChangedMessage: i = "",
    } = {}) {
        const o = Array.isArray(r)
            ? r.filter(
                  (a) =>
                      a &&
                      typeof a.type == "string" &&
                      typeof a.label == "string" &&
                      typeof a.subject == "string" &&
                      typeof a.body == "string",
              )
            : [];
        return {
            interviewMode: t === "onsite" ? "onsite" : "online",
            templateContext: e === "reschedule" ? "reschedule" : "schedule",
            templates: o,
            emailSubject: typeof n == "string" ? n : "",
            emailBody: typeof s == "string" ? s : "",
            modeChangedMessage: i,
            selectedTemplateType: "",
            selectedTemplateLabel: "",
            selectedTemplateSubject: "",
            selectedTemplateBody: "",
            lastSelectedTemplateType: "",
            modeChangeNotice: "",
            get availableTemplates() {
                return this.templateContext === "reschedule"
                    ? this.templates.filter(
                          (a) => a.type === "interview_reschedule",
                      )
                    : this.templates.filter((a) => k_.has(a.type));
            },
            init() {
                this.$watch("interviewMode", (a) => this.handleModeChange(a));
            },
            openTemplatePicker() {
                const a = this.$refs.templateDialog;
                !a ||
                    a.open ||
                    this.availableTemplates.length === 0 ||
                    (a.showModal(),
                    this.$nextTick(() =>
                        a.querySelector("[data-template-option]")?.focus(),
                    ));
            },
            closeTemplatePicker() {
                const a = this.$refs.templateDialog;
                a?.open && a.close();
            },
            selectTemplate(a) {
                const l = this.availableTemplates.find(
                    (f) => f.type === a?.type,
                );
                l &&
                    (this.templateContext === "schedule" &&
                        (this.interviewMode =
                            l.type === "interview_onsite"
                                ? "onsite"
                                : "online"),
                    (this.emailSubject = l.subject),
                    (this.emailBody = l.body),
                    (this.selectedTemplateType = l.type),
                    (this.selectedTemplateLabel = l.label),
                    (this.selectedTemplateSubject = l.subject),
                    (this.selectedTemplateBody = l.body),
                    (this.lastSelectedTemplateType = l.type),
                    (this.modeChangeNotice = ""),
                    this.closeTemplatePicker());
            },
            handleModeChange(a) {
                if (
                    this.templateContext !== "schedule" ||
                    !this.selectedTemplateType ||
                    (this.selectedTemplateType === "interview_onsite"
                        ? "onsite"
                        : "online") === a
                )
                    return;
                (this.emailSubject === this.selectedTemplateSubject &&
                this.emailBody === this.selectedTemplateBody
                    ? ((this.emailSubject = ""), (this.emailBody = ""))
                    : (this.modeChangeNotice = this.modeChangedMessage),
                    (this.selectedTemplateType = ""),
                    (this.selectedTemplateLabel = ""),
                    (this.selectedTemplateSubject = ""),
                    (this.selectedTemplateBody = ""));
            },
        };
    }
    function P_(t) {
        t.data("interviewEmailTemplatePicker", (e) => I_(e));
    }
    function M_(t) {
        t.data("adminUserDocumentViewer", (e = []) => ({
            documents: e,
            selectedId: null,
            get activeDocument() {
                return (
                    this.documents.find((r) => r.id === this.selectedId) ?? null
                );
            },
            select(r) {
                this.selectedId = r;
            },
        }));
    }
    const B_ = new Set(["success", "error", "warning", "info"]),
        G0 = (t) => (B_.has(t) ? t : "info"),
        Ln = (t, e, r) => e?.getAttribute?.(r) ?? t.getAttribute(r);
    function $_(
        t,
        { createInput: e = () => document.createElement("input") } = {},
    ) {
        const r = new WeakSet();
        let n = new WeakSet();
        const s = new WeakMap(),
            i = async (o) => {
                const a = o.target;
                if (a?.tagName !== "FORM") return;
                const l = s.get(a);
                if (l && l.submitter === o.submitter) {
                    l.event = o;
                    return;
                }
                if (n.has(a)) {
                    o.preventDefault();
                    return;
                }
                const f = Ln(a, o.submitter, "data-confirm");
                if (f && (o.preventDefault(), !r.has(a))) {
                    (r.add(a),
                        a
                            .querySelectorAll("[data-modal-confirmed]")
                            .forEach((u) => u.remove()));
                    try {
                        if (
                            !(await t(f, {
                                title:
                                    Ln(a, o.submitter, "data-confirm-title") ||
                                    "Confirm action",
                                type:
                                    Ln(a, o.submitter, "data-confirm-type") ||
                                    "warning",
                                confirmLabel:
                                    Ln(a, o.submitter, "data-confirm-label") ||
                                    "Confirm",
                            })) ||
                            !a.isConnected
                        )
                            return;
                        const d = Ln(a, o.submitter, "data-confirm-field");
                        if (
                            d &&
                            /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(d) &&
                            !["_token", "_method"].includes(d)
                        ) {
                            const g = e();
                            ((g.type = "hidden"),
                                (g.name = d),
                                (g.value = "1"),
                                g.setAttribute("data-modal-confirmed", ""),
                                a.append(g));
                        }
                        const h = { submitter: o.submitter, event: null };
                        (s.set(a, h),
                            a.requestSubmit(o.submitter || void 0),
                            h.event && !h.event.defaultPrevented && n.add(a));
                    } finally {
                        (s.delete(a), r.delete(a));
                    }
                }
            };
        return (
            (i.reset = () => {
                n = new WeakSet();
            }),
            i
        );
    }
    function D_({ document: t = document, window: e = window } = {}) {
        const r = t.querySelector("[data-alert-dialog]"),
            n = t.querySelector("[data-toast-list]");
        if (!r || !n) return null;
        const s = r.querySelector("[data-alert-title]"),
            i = r.querySelector("[data-alert-message]"),
            o = r.querySelector("[data-alert-confirm]"),
            a = r.querySelector("[data-alert-cancel]"),
            l = r.querySelector("[data-alert-dismiss]"),
            f = [];
        let u = null;
        const d = () => {
                if (u || !f.length) return;
                u = f.shift();
                const y = u.options,
                    S = G0(y.type || (y.confirmation ? "warning" : "info"));
                ((s.textContent =
                    y.title ||
                    (y.confirmation
                        ? "Confirm action"
                        : {
                              success: "Success",
                              error: "Something went wrong",
                              warning: "Please check",
                              info: "Information",
                          }[S])),
                    (i.textContent = y.message),
                    (r.dataset.alertType = S),
                    r.querySelectorAll("[data-alert-icon]").forEach((N) => {
                        N.hidden = N.dataset.alertIcon !== S;
                    }),
                    (a.hidden = !y.confirmation),
                    (o.textContent =
                        y.confirmLabel || (y.confirmation ? "Confirm" : "OK")),
                    o.classList.toggle("nw-button--danger", S === "error"),
                    o.classList.toggle("nw-button--primary", S !== "error"),
                    r.showModal(),
                    (y.confirmation ? a : o).focus());
            },
            h = (y) => {
                if (!u) return;
                const S = u;
                ((u = null),
                    r.close(),
                    S.focus?.isConnected &&
                        S.focus.focus({ preventScroll: !0 }),
                    S.resolve(y),
                    d());
            },
            g = (y) =>
                new Promise((S) => {
                    (f.push({
                        options: y,
                        resolve: S,
                        focus: r.contains(t.activeElement)
                            ? u?.focus
                            : t.activeElement,
                    }),
                        d());
                });
        (o.addEventListener("click", () => h(!0)),
            a.addEventListener("click", () => h(!1)),
            l.addEventListener("click", () => h(!1)),
            r.addEventListener("cancel", (y) => {
                (y.preventDefault(), h(!1));
            }),
            r.addEventListener("keydown", (y) => {
                if (y.key !== "Tab") return;
                const S = [
                        ...r.querySelectorAll(
                            "button, [href], input, select, textarea, [tabindex]",
                        ),
                    ].filter(
                        (q) => !q.disabled && !q.hidden && q.tabIndex >= 0,
                    ),
                    N = S[0],
                    P = S.at(-1);
                y.shiftKey && t.activeElement === N
                    ? (y.preventDefault(), P?.focus())
                    : !y.shiftKey &&
                      t.activeElement === P &&
                      (y.preventDefault(), N?.focus());
            }));
        const v = (y, { type: S = "info", duration: N } = {}) => {
                if (typeof y != "string" || !y.trim()) return;
                const P = G0(S);
                N = Number.isFinite(N)
                    ? Math.max(0, N)
                    : ["warning", "error"].includes(P)
                      ? 0
                      : 6500;
                const q = t.createElement("li");
                ((q.className = `nw-toast nw-toast--${P}`),
                    (q.dataset.toastType = P),
                    q.setAttribute(
                        "role",
                        ["error", "warning"].includes(P) ? "alert" : "status",
                    ),
                    q.setAttribute("aria-atomic", "true"));
                const F = t
                    .querySelector(`[data-alert-icon="${P}"] svg`)
                    ?.cloneNode(!0);
                F && (F.classList.add("nw-toast__icon"), q.append(F));
                const te = t.createElement("p");
                ((te.className = "nw-toast__message"),
                    (te.textContent = y),
                    q.append(te));
                const re = t.createElement("button");
                ((re.type = "button"),
                    (re.className = "nw-toast__close"),
                    re.setAttribute("aria-label", "Dismiss notification"),
                    (re.textContent = "×"),
                    q.append(re));
                let de = null,
                    ce = 0,
                    w = N;
                const R = () => {
                        (de !== null && e.clearTimeout(de), q.remove());
                    },
                    T = () => {
                        de !== null &&
                            (e.clearTimeout(de),
                            (de = null),
                            (w = Math.max(0, w - (e.performance.now() - ce))));
                    },
                    k = () => {
                        !N ||
                            de !== null ||
                            q.matches(":hover, :focus-within") ||
                            ((ce = e.performance.now()),
                            (de = e.setTimeout(R, w)));
                    };
                (re.addEventListener("click", R),
                    q.addEventListener("pointerenter", T),
                    q.addEventListener("pointerleave", k),
                    q.addEventListener("focusin", T),
                    q.addEventListener("focusout", (m) => {
                        q.contains(m.relatedTarget) || k();
                    }));
                const M = n.querySelectorAll("[data-dynamic-toast]");
                return (
                    M.length >= 5 &&
                        M[0].dispatchEvent(new Event("nw:toast-dismiss")),
                    q.setAttribute("data-dynamic-toast", ""),
                    q.addEventListener("nw:toast-dismiss", R),
                    n.append(q),
                    k(),
                    { dismiss: R }
                );
            },
            b = {
                notify: v,
                alert: (y, S = {}) =>
                    g({ ...S, message: String(y), confirmation: !1 }),
                confirm: (y, S = {}) =>
                    g({ ...S, message: String(y), confirmation: !0 }),
            },
            _ = $_(b.confirm, { createInput: () => t.createElement("input") });
        return (
            t.addEventListener("submit", _),
            e.addEventListener("pageshow", _.reset),
            e.addEventListener("nw:notify", (y) => {
                y.detail?.message && v(y.detail.message, y.detail);
            }),
            (e.Marthire = { ...(e.Marthire || {}), ...b }),
            b
        );
    }
    class da extends Error {
        constructor(e, r = 0, n = {}) {
            (super(e), (this.status = r), (this.errors = n));
        }
    }
    async function Du(t) {
        let e;
        try {
            e = await t.json();
        } catch {
            e = null;
        }
        if (t.redirected || !e || typeof e != "object")
            throw new da(
                "This page could not be refreshed. Your session may have expired. Please sign in again if needed.",
                t.status,
            );
        if (!t.ok) {
            const r = {
                401: "Your session has expired. Please sign in again.",
                403: "You no longer have permission to perform this action.",
                404: "This list or record is no longer available.",
                419: "Your session has expired. Please sign in again before submitting.",
                429: "Too many requests. Please wait a moment and try again.",
            };
            throw new da(
                r[t.status] ||
                    (t.status >= 500
                        ? "The server could not complete this request. Please try again."
                        : e.message) ||
                    "The request failed. Please try again.",
                t.status,
                e.errors || {},
            );
        }
        return e;
    }
    function j_(t = fetch) {
        let e = null,
            r = 0;
        return {
            invalidate() {
                ((r += 1), e?.abort(), (e = null));
            },
            async run(n, s = {}) {
                this.invalidate();
                const i = r,
                    o = new AbortController();
                e = o;
                try {
                    const a = await t(n, {
                            ...s,
                            signal: o.signal,
                            credentials: "same-origin",
                            cache: "no-store",
                        }),
                        l = await Du(a);
                    return r === i ? l : null;
                } catch (a) {
                    if (r !== i || a.name === "AbortError") return null;
                    throw a;
                } finally {
                    r === i && (e = null);
                }
            },
        };
    }
    function Z0(t, e, r, n = ["page"]) {
        const s = new URL(t || r, r),
            i = new URL(r);
        if (s.origin !== i.origin)
            throw new da("Table requests must stay on this site.");
        const o = new Set(s.searchParams.keys());
        for (const [l, f] of i.searchParams)
            o.has(l) || s.searchParams.append(l, f);
        const a = [...e];
        for (const l of new Set(a.map(([f]) => f))) s.searchParams.delete(l);
        for (const [l, f] of a)
            typeof f == "string" && f !== "" && s.searchParams.append(l, f);
        return (
            n.forEach((l) => s.searchParams.delete(l)),
            (s.hash = ""),
            s.href
        );
    }
    function U_(t, e) {
        return !t.length || !e.length || t.some((r) => e.includes(r));
    }
    function F_({
        document: t = document,
        window: e = window,
        fetch: r = fetch,
        Alpine: n,
    } = {}) {
        const s = new Map(),
            i = new Map(),
            o = new Set(),
            a = [],
            l = e.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`;
        let f = !1,
            u = null;
        const d = (m, A, O) => {
                (m.addEventListener(A, O),
                    a.push(() => m.removeEventListener(A, O)));
            },
            h = (m, A = "info") => e.Marthire?.notify(m, { type: A }),
            g = (m) =>
                (m || "")
                    .split(",")
                    .map((A) => A.trim())
                    .filter(Boolean),
            v = (m) =>
                m?.isConnected &&
                m.getClientRects().length > 0 &&
                e.getComputedStyle(m).visibility !== "hidden",
            b = (m) => {
                const A = new URL(m, e.location.href);
                if (A.origin !== e.location.origin)
                    throw new Error("Table requests must stay on this site.");
                return A.href;
            };
        function _(m, A, O = !1) {
            let B = m.root.querySelector("[data-live-status-bar]");
            if (!B) {
                ((B = t.createElement("div")),
                    (B.className = "nw-live-table-status"),
                    B.setAttribute("data-live-status-bar", ""));
                const I = t.createElement("span");
                (I.setAttribute("data-live-status", ""),
                    I.setAttribute("role", "status"),
                    I.setAttribute("aria-live", "polite"),
                    (I.tabIndex = -1));
                const $ = t.createElement("button");
                (($.type = "button"),
                    ($.className = "nw-link nw-link--small"),
                    $.setAttribute("data-live-refresh", ""),
                    ($.textContent = "Refresh"),
                    B.append(I, $),
                    m.root.prepend(B));
            }
            (B.classList.toggle("has-error", O),
                (B.querySelector("[data-live-status]").textContent = A));
        }
        function y() {
            t.querySelectorAll("[data-live-table]").forEach((m) => {
                const A = m.dataset.liveTable;
                s.has(A)
                    ? (s.get(A).root = m)
                    : s.set(A, {
                          id: A,
                          root: m,
                          url: b(m.dataset.liveUrl || e.location.href),
                          topics: g(m.dataset.liveTopics),
                          pages: g(m.dataset.livePages || "page"),
                          transport: j_(r),
                          sequence: 0,
                          loading: !1,
                          mutating: !1,
                      });
            });
            for (const [m, A] of s)
                A.root.isConnected || (A.transport.invalidate(), s.delete(m));
        }
        function S(m) {
            for (const A of o) {
                if (!A.isConnected) {
                    o.delete(A);
                    continue;
                }
                const O = A.closest('[role="dialog"]');
                O && !v(O) && (A.reset(), re(A), o.delete(A));
            }
            return (
                [...o].some((A) => m.root.contains(A)) ||
                [...t.querySelectorAll('[role="dialog"]')].some(v) ||
                !!t.querySelector("dialog[open]")
            );
        }
        function N(m) {
            const A = t.activeElement;
            return m.contains(A)
                ? { id: A.id, start: A.selectionStart, end: A.selectionEnd }
                : null;
        }
        function P(m) {
            const O = m
                .closest('[role="dialog"]')
                ?.getAttribute("aria-labelledby");
            O?.endsWith("-title") &&
                e.dispatchEvent(
                    new CustomEvent("close-modal", { detail: O.slice(0, -6) }),
                );
        }
        async function q(m, A, O) {
            const B = A.regions?.[m.id];
            if (typeof B != "string")
                throw new Error(
                    "The server did not return the requested table.",
                );
            const $ = [
                ...new e.DOMParser()
                    .parseFromString(B, "text/html")
                    .querySelectorAll("[data-live-table]"),
            ].find((X) => X.dataset.liveTable === m.id);
            if (!$)
                throw new Error("The table response could not be displayed.");
            $.querySelectorAll("script").forEach((X) => X.remove());
            const D = N(m.root),
                V = m.root.matches(".nw-review")
                    ? m.root
                    : m.root.querySelector(".nw-review"),
                Z = $.matches(".nw-review") ? $ : $.querySelector(".nw-review");
            V &&
                Z &&
                ((Z.dataset.restoreReviewTab =
                    V.dataset.currentReviewTab || ""),
                (Z.dataset.restoreDocument = V.dataset.currentDocument || ""));
            const U = m.root,
                W = () => {
                    (n?.destroyTree(U),
                        U.replaceWith($),
                        (m.root = $),
                        n?.initTree($));
                };
            (n ? n.mutateDom(W) : W(),
                (m.url = b(A.url || m.url)),
                (m.pendingFilterUrl = null),
                (m.deferredRefresh = null),
                (m.root.dataset.liveUrl = m.url),
                _(m, "Up to date"),
                typeof A.title == "string" && (t.title = A.title),
                O === "push" && m.url !== e.location.href
                    ? e.history.pushState({ liveTable: m.id }, "", m.url)
                    : (O === "replace" || (O && m.url !== e.location.href)) &&
                      e.history.replaceState({ liveTable: m.id }, "", m.url),
                [...t.querySelectorAll('[role="dialog"]')].some(v) ||
                    t.body.classList.remove("overflow-y-hidden"),
                await n?.nextTick());
            const Y = D?.id ? t.getElementById(D.id) : null;
            if (Y && v(Y)) {
                if (
                    (Y.focus({ preventScroll: !0 }), typeof D.start == "number")
                )
                    try {
                        Y.setSelectionRange(D.start, D.end);
                    } catch {}
            } else
                D &&
                    m.root
                        .querySelector("[data-live-status]")
                        ?.focus({ preventScroll: !0 });
        }
        async function F(
            m,
            A = m.deferredRefresh?.url || m.pendingFilterUrl || m.url,
            {
                background: O = !1,
                history: B = m.deferredRefresh?.history || "replace",
                ignoreFocus: I = !1,
            } = {},
        ) {
            if (f || !m.root.isConnected || m.mutating) return;
            if (
                O &&
                (t.hidden ||
                    e.navigator.onLine === !1 ||
                    S(m) ||
                    (!I &&
                        m.root.contains(t.activeElement) &&
                        t.activeElement.matches("input,textarea,select")))
            ) {
                _(m, "Updates paused while you are editing.");
                return;
            }
            const $ = ++m.sequence;
            ((m.loading = !0),
                m.root.setAttribute("aria-busy", "true"),
                _(m, "Updating…"));
            try {
                const D = await m.transport.run(b(A), {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-Live-Tables": m.id,
                    },
                });
                if (!D || f) return;
                if (S(m)) {
                    ((m.deferredRefresh = { url: A, history: B }),
                        _(m, "Updates paused while you are editing."));
                    return;
                }
                await q(m, D, B);
            } catch (D) {
                (_(m, D.message || "Could not refresh. Please try again.", !0),
                    O ||
                        h(
                            D.message || "Could not refresh the table.",
                            "error",
                        ));
            } finally {
                $ === m.sequence &&
                    ((m.loading = !1),
                    m.root.setAttribute("aria-busy", "false"));
            }
        }
        async function te(m) {
            if (!S(m)) return !0;
            if (
                !(await e.Marthire?.confirm(
                    "Refresh this table and discard the unsaved changes?",
                    {
                        title: "Unsaved changes",
                        type: "warning",
                        confirmLabel: "Discard and refresh",
                    },
                ))
            )
                return !1;
            for (const O of o)
                m.root.contains(O) && (O.reset(), re(O), o.delete(O));
            return (
                m.root.querySelectorAll('[role="dialog"]').forEach((O) => {
                    v(O) && P(O);
                }),
                await n?.nextTick(),
                !0
            );
        }
        function re(m) {
            (m
                .querySelectorAll("[data-live-validation]")
                .forEach((A) => A.remove()),
                m.querySelectorAll("[data-live-invalid]").forEach((A) => {
                    (A.removeAttribute("aria-invalid"),
                        A.removeAttribute("data-live-invalid"));
                }));
        }
        function de(m, A) {
            re(m);
            const O = t.createElement("div");
            ((O.className = "nw-notice nw-notice--error"),
                O.setAttribute("data-live-validation", ""),
                O.setAttribute("role", "alert"),
                (O.tabIndex = -1));
            const B = t.createElement("ul");
            for (const [I, $] of Object.entries(A)) {
                const D = Array.isArray($) ? $[0] : $;
                if (typeof D != "string") continue;
                const V = t.createElement("li");
                ((V.textContent = D), B.append(V));
                const Z = [...m.elements].find(
                    (U) => U.name === I && U.type !== "hidden",
                );
                if (Z) {
                    (Z.setAttribute("aria-invalid", "true"),
                        Z.setAttribute("data-live-invalid", ""));
                    const U = t.createElement("p");
                    ((U.className = "nw-field__error"),
                        (U.textContent = D),
                        U.setAttribute("data-live-validation", ""),
                        Z.after(U));
                }
            }
            (O.append(B), m.prepend(O), O.focus({ preventScroll: !1 }));
        }
        function ce(m, A) {
            const O = { topics: m, source: A, origin: l, at: Date.now() };
            e.dispatchEvent(new CustomEvent("nw:data-changed", { detail: O }));
        }
        async function w(m, A, O) {
            if (m.mutating) return;
            const B = Symbol("table mutation");
            ((m.mutating = B), m.transport.invalidate(), re(A));
            const I = new e.FormData(A);
            O?.name && I.append(O.name, O.value);
            const $ = [...A.elements].filter((D) => !D.disabled);
            ($.forEach((D) => {
                D.disabled = !0;
            }),
                _(m, "Saving…"));
            try {
                const D = await r(b(A.action || e.location.href), {
                        method: (A.method || "post").toUpperCase(),
                        body: I,
                        credentials: "same-origin",
                        cache: "no-store",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-Live-Action": "1",
                        },
                    }),
                    V = await Du(D);
                (h(V.message || "Saved successfully.", V.type || "success"),
                    o.delete(A),
                    A.hasAttribute("data-live-reset") && A.reset());
                const Z = A.closest('[role="dialog"]');
                ((Z && [...o].some((Y) => Y !== A && Z.contains(Y))) || P(A),
                    (m.mutating = !1),
                    await n?.nextTick(),
                    ce(Array.isArray(V.topics) ? V.topics : m.topics, m.id));
                const W = m.deferredRefresh?.url || m.pendingFilterUrl || m.url;
                await F(m, W, {
                    background: !0,
                    history: "replace",
                    ignoreFocus: !0,
                });
            } catch (D) {
                (D.status === 422 && de(A, D.errors),
                    h(
                        D.message ||
                            "The action failed. Your changes have been preserved.",
                        "error",
                    ),
                    _(m, "Not saved. Check the form and try again.", !0));
            } finally {
                (m.mutating === B && (m.mutating = !1),
                    $.forEach((D) => {
                        D.isConnected && (D.disabled = !1);
                    }));
            }
        }
        function R(m) {
            const A = m.closest?.("[data-live-table]");
            return A ? (y(), s.get(A.dataset.liveTable)) : null;
        }
        function T(m, A = !1) {
            const O = R(m);
            O &&
                (O.transport.invalidate(),
                (O.pendingFilterUrl = Z0(
                    m.action,
                    new e.FormData(m),
                    O.url,
                    O.pages,
                )),
                clearTimeout(i.get(O.id)),
                i.set(
                    O.id,
                    setTimeout(
                        async () => {
                            (await te(O)) &&
                                F(O, O.pendingFilterUrl, {
                                    history: "replace",
                                });
                        },
                        A ? 0 : 300,
                    ),
                ));
        }
        (d(t, "input", (m) => {
            const A = m.target.form;
            !A ||
                !R(A) ||
                (A.hasAttribute("data-live-filter")
                    ? ["text", "search", "number"].includes(m.target.type) &&
                      T(A)
                    : o.add(A));
        }),
            d(t, "change", (m) => {
                const A = m.target.form;
                !A ||
                    !R(A) ||
                    (A.hasAttribute("data-live-filter") ? T(A, !0) : o.add(A));
            }),
            d(t, "submit", async (m) => {
                if (m.defaultPrevented) return;
                const A = m.target,
                    O = R(A);
                !O ||
                    A.hasAttribute("data-live-ignore") ||
                    (m.preventDefault(),
                    (A.method || "get").toLowerCase() === "get"
                        ? (await te(O)) &&
                          F(
                              O,
                              Z0(A.action, new e.FormData(A), O.url, O.pages),
                              { history: "push" },
                          )
                        : w(O, A, m.submitter));
            }),
            d(t, "click", async (m) => {
                const A = m.target.closest?.("[data-live-refresh]");
                if (A) {
                    const I = R(A);
                    I && (await te(I)) && F(I);
                    return;
                }
                const O = m.target.closest?.("a");
                if (
                    !O ||
                    m.defaultPrevented ||
                    m.button !== 0 ||
                    m.ctrlKey ||
                    m.metaKey ||
                    m.shiftKey ||
                    m.altKey ||
                    O.hasAttribute("download") ||
                    (O.target && O.target !== "_self")
                )
                    return;
                const B = R(O);
                !B ||
                    !(
                        O.hasAttribute("data-live-link") ||
                        O.closest(".nw-pagination")
                    ) ||
                    O.origin !== e.location.origin ||
                    (m.preventDefault(),
                    (await te(B)) && F(B, O.href, { history: "push" }));
            }));
        function k(m = {}, A = !1) {
            (y(),
                s.forEach((O) => {
                    (m.initial &&
                        Number(O.root.dataset.liveInterval || 0) === 0) ||
                        (A && m.origin === l && m.source === O.id) ||
                        (U_(
                            O.topics,
                            Array.isArray(m.topics) ? m.topics : [],
                        ) &&
                            F(O, void 0, { background: !0 }));
                }));
        }
        d(e, "nw:data-changed", (m) => {
            const A = { ...m.detail, origin: l };
            if ((k(A, !0), u)) u.postMessage(A);
            else
                try {
                    e.localStorage.setItem(
                        "marthire:table-change",
                        JSON.stringify(A),
                    );
                } catch {}
        });
        try {
            ((u = new e.BroadcastChannel("marthire:tables")),
                (u.onmessage = (m) => k(m.data)));
        } catch {}
        (d(e, "storage", (m) => {
            if (!u && m.key === "marthire:table-change")
                try {
                    k(JSON.parse(m.newValue));
                } catch {}
        }),
            d(e, "focus", () => k()),
            d(t, "visibilitychange", () => {
                t.hidden || k();
            }),
            d(e, "popstate", () => {
                (y(),
                    s.forEach((m) => {
                        S(m) || F(m, e.location.href, { history: "replace" });
                    }));
            }),
            y());
        const M = e.setInterval(() => {
            s.forEach((m) => {
                Number(m.root.dataset.liveInterval || 0) > 0 &&
                    !m.loading &&
                    F(m, void 0, { background: !0 });
            });
        }, 3e4);
        return (
            queueMicrotask(() => {
                const m = t.querySelector("[data-live-changes]");
                if (m)
                    try {
                        ce(JSON.parse(m.dataset.liveChanges));
                        return;
                    } catch {}
                k({ initial: !0 });
            }),
            {
                refreshAll: k,
                destroy() {
                    ((f = !0),
                        a.forEach((m) => m()),
                        s.forEach((m) => m.transport.invalidate()),
                        i.forEach(clearTimeout),
                        e.clearInterval(M),
                        u?.close());
                },
            }
        );
    }
    window.Alpine = we;
    ym(we);
    _m(we);
    N_(we);
    O_(we);
    wm(we);
    C_(we);
    q_(we);
    P_(we);
    M_(we);
    D_();
    we.data("resumeAutofill", (t = {}) => ({
        dragging: !1,
        extracting: !1,
        fileName: "",
        message: "",
        error: "",
        async extract(e) {
            const r = e?.files?.[0];
            if (r) {
                ((this.fileName = r.name),
                    (this.message = ""),
                    (this.error = ""),
                    (this.extracting = !0));
                try {
                    const { extractResumeText: n } = await pa(
                            async () => {
                                const { extractResumeText: o } =
                                    await import("./candidate-resume-autofill-CBxBDr2u.js");
                                return { extractResumeText: o };
                            },
                            __vite__mapDeps([2, 1]),
                        ),
                        s = await n(r),
                        i = this.fillRecognizedFields(s);
                    this.message = i
                        ? (t.success ??
                          "We filled fields we could recognize. Review and save each section.")
                        : (t.noMatches ??
                          "The résumé was read, but no profile fields could be matched. You can still upload it and fill the sections manually.");
                } catch (n) {
                    this.message =
                        n?.message ||
                        t.unsupported ||
                        "This file can still be uploaded. Complete your profile manually if text autofill is unavailable.";
                } finally {
                    this.extracting = !1;
                }
            }
        },
        fillRecognizedFields(e) {
            const r = e
                    .split(/\r?\n/)
                    .map((b) => b.replace(/\s+/g, " ").trim())
                    .filter(Boolean),
                n = (b, _) => {
                    if (!_) return !1;
                    const y = document.querySelectorAll(b);
                    let S = !1;
                    return (
                        y.forEach((N) => {
                            N.value?.trim() ||
                                ((N.value = _),
                                N.dispatchEvent(
                                    new Event("input", { bubbles: !0 }),
                                ),
                                N.dispatchEvent(
                                    new Event("change", { bubbles: !0 }),
                                ),
                                (S = !0));
                        }),
                        S
                    );
                };
            let s = !1;
            const i =
                    /^(?:professional\s+)?(?:summary|profile|objective|about\s+me)$/i,
                o =
                    /^(?:technical\s+)?(?:skills|key\s+skills|core\s+competencies|experience|work\s+experience|employment\s+history|education|education\s+and\s+qualifications|projects|certifications|references)$/i,
                a = (b, _ = o) => {
                    const y = r.findIndex((N) => b.test(N));
                    if (y < 0) return [];
                    const S = [];
                    for (const N of r.slice(y + 1)) {
                        if (_.test(N)) break;
                        S.push(N);
                    }
                    return S;
                },
                l = r
                    .slice(0, 8)
                    .find(
                        (b) =>
                            /^[\p{L}][\p{L} .'-]{2,79}$/u.test(b) &&
                            b.trim().split(/\s+/).length >= 2 &&
                            !i.test(b),
                    );
            if (l) {
                const b = l.split(/\s+/);
                ((s = n('[name="first_name"]', b.shift()) || s),
                    (s = n('[name="last_name"]', b.join(" ")) || s));
            }
            const f = a(i)
                .join(
                    `
`,
                )
                .slice(0, 3e3);
            s = n('[name="about"]', f) || s;
            const u = a(
                /^(?:technical\s+)?(?:skills|key\s+skills|core\s+competencies)$/i,
            )
                .join(", ")
                .split(/[,;|•]+/)
                .map((b) => b.trim())
                .filter(Boolean)
                .slice(0, 30)
                .join(", ");
            s = n('[name="skills"]', u) || s;
            const d = {
                linkedin: /https?:\/\/(?:www\.)?linkedin\.com\/[\w./?=%-]+/i,
                github: /https?:\/\/(?:www\.)?github\.com\/[\w./?=%-]+/i,
            };
            for (const [b, _] of Object.entries(d)) {
                const y = e.match(_)?.[0]?.replace(/[),.;]+$/, "");
                s = n(`[name="links[${b}]"]`, y) || s;
            }
            const h = r
                .map(
                    (b) =>
                        b.match(
                            /https?:\/\/(?:www\.)?(?!linkedin\.com|github\.com)[\w.-]+\.[a-z]{2,}(?:\/[^\s]*)?/i,
                        )?.[0],
                )
                .find(Boolean);
            s = n('[name="links[website]"]', h?.replace(/[),.;]+$/, "")) || s;
            const v = r
                .find((b) => /(?:phone|mobile|tel\.?|\+\d)/i.test(b))
                ?.match(/\+?\d[\d\s().-]{6,}\d/)?.[0]
                ?.trim();
            return ((s = n('[name="phone"]', v) || s), s);
        },
    }));
    we.data("applicantProfileChecklistEditor", () => ({
        activeSection: null,
        successMessage: "",
        open(t) {
            ((this.activeSection = t),
                (this.successMessage = ""),
                this.$dispatch(
                    "open-modal",
                    "applicant-profile-checklist-editor",
                ));
        },
        clearFieldError(t) {
            const e = t.target;
            if (!e?.matches?.('[aria-invalid="true"]')) return;
            (e.removeAttribute("aria-invalid"),
                (e.closest(".nw-field, .nw-form-row, label") || e.parentElement)
                    ?.querySelectorAll("[data-async-field-error]")
                    .forEach((n) => {
                        n.dataset.asyncFieldError === e.name && n.remove();
                    }));
        },
        showFormError(t, e) {
            let r = t.querySelector("[data-async-form-error]");
            (r ||
                ((r = document.createElement("p")),
                (r.className = "nw-field__error"),
                r.setAttribute("role", "alert"),
                (r.dataset.asyncFormError = ""),
                t.prepend(r)),
                (r.textContent = e));
        },
        clearFormErrors(t) {
            (t
                .querySelectorAll(
                    "[data-async-field-error], [data-async-form-error]",
                )
                .forEach((e) => e.remove()),
                t
                    .querySelectorAll('[aria-invalid="true"]')
                    .forEach((e) => e.removeAttribute("aria-invalid")));
        },
        showValidationErrors(t, e) {
            this.clearFormErrors(t);
            const r = [...t.elements].filter((s) => s.name);
            (Object.entries(e || {}).forEach(([s, i]) => {
                const o = s.replace(/\[(.*?)\]/g, ".$1"),
                    a = r.filter((l) => {
                        const f = l.name
                            .replace(/\[(.*?)\]/g, ".$1")
                            .replace(/\[\]/g, "");
                        return f === o || o === f || o.startsWith(`${f}.`);
                    });
                if (!a.length) {
                    this.showFormError(t, (i || []).join(" "));
                    return;
                }
                a.forEach((l) => {
                    l.setAttribute("aria-invalid", "true");
                    const f =
                            l.closest(".nw-field, .nw-form-row, label") ||
                            l.parentElement,
                        u = document.createElement("p");
                    ((u.className = "nw-field__error"),
                        (u.dataset.asyncFieldError = l.name),
                        u.setAttribute("role", "alert"),
                        (u.textContent = (i || []).join(" ")),
                        f?.append(u));
                });
            }),
                t
                    .querySelector('[aria-invalid="true"]')
                    ?.focus({ preventScroll: !0 }));
        },
        replaceServerMarkup(t, e) {
            if (typeof e != "string") return;
            const n = new DOMParser()
                    .parseFromString(e, "text/html")
                    .querySelector(t),
                s = document.querySelector(t);
            if (!n || !s) return;
            n.querySelectorAll("script").forEach((o) => o.remove());
            const i = () => {
                (we.destroyTree(s), s.replaceWith(n), we.initTree(n));
            };
            we.mutateDom(i);
        },
        async submitForm(t) {
            const e = t.target;
            if (
                !(e instanceof HTMLFormElement) ||
                !e.matches("[data-applicant-profile-async-form]")
            )
                return;
            (t.preventDefault(),
                this.clearFormErrors(e),
                (this.successMessage = ""));
            const r = [
                    ...e.querySelectorAll(
                        'button[type="submit"], input[type="submit"]',
                    ),
                ],
                n = r.map((i) => i.disabled),
                s = r.map((i) =>
                    i instanceof HTMLInputElement ? i.value : i.textContent,
                );
            r.forEach((i) => {
                ((i.disabled = !0),
                    i.setAttribute("aria-busy", "true"),
                    i instanceof HTMLInputElement
                        ? (i.value = "Saving…")
                        : (i.textContent = "Saving…"));
            });
            try {
                const i = await fetch(e.action, {
                        method: (e.method || "POST").toUpperCase(),
                        body: new FormData(e),
                        credentials: "same-origin",
                        headers: {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    }),
                    o = await i.json().catch(() => ({}));
                if (!i.ok) {
                    i.status === 422 && o.errors
                        ? this.showValidationErrors(e, o.errors)
                        : this.showFormError(
                              e,
                              o.message ||
                                  "Your changes could not be saved. Please try again.",
                          );
                    return;
                }
                ((this.successMessage =
                    o.message || "Your profile was updated."),
                    this.replaceServerMarkup(
                        "#profile-checklist",
                        o.checklist_html,
                    ),
                    this.replaceServerMarkup(
                        "[data-applicant-profile-editor-content]",
                        o.editor_html,
                    ));
            } catch {
                this.showFormError(
                    e,
                    "We could not reach the server. Check your connection and try again.",
                );
            } finally {
                r.forEach((i, o) => {
                    i.isConnected &&
                        ((i.disabled = n[o]),
                        i.removeAttribute("aria-busy"),
                        i instanceof HTMLInputElement
                            ? (i.value = s[o])
                            : (i.textContent = s[o]));
                });
            }
        },
    }));
    const H_ = (t) => {
        const e = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        t?.scrollIntoView({ behavior: e ? "auto" : "smooth", block: "start" });
    };
    we.data("jobBoard", (t, e = 6) => ({
        jobs: t,
        limit: e,
        filter: "all",
        get matches() {
            return this.jobs.reduce(
                (r, n, s) => (
                    (this.filter === "all" ||
                        (this.filter === "remote"
                            ? n.workplace === "Remote"
                            : n.type === this.filter)) &&
                        r.push(s),
                    r
                ),
                [],
            );
        },
        get visible() {
            return this.matches.slice(0, this.limit);
        },
        get summary() {
            const r = this.matches.length;
            return r === 0
                ? "No roles found"
                : `Showing ${this.visible.length} of ${r} ${r === 1 ? "role" : "roles"}`;
        },
        isShown(r) {
            return this.visible.includes(r);
        },
    }));
    we.data("jobSearch", ({ jobs: t, categories: e, pageSize: r = 9 }) => ({
        jobs: t,
        categories: e,
        pageSize: r,
        query: "",
        place: "",
        category: "",
        types: [],
        workplaces: [],
        sort: "newest",
        shown: r,
        filtersOpen: !1,
        init() {
            const n = new URLSearchParams(window.location.search),
                s = n.get("category") ?? "";
            ((this.query = n.get("q") ?? ""),
                (this.place = n.get("place") ?? ""),
                (this.category = s in this.categories ? s : ""),
                (this.types = n.getAll("type")),
                (this.workplaces = n.getAll("workplace")),
                (this.sort = n.get("sort") === "title" ? "title" : "newest"),
                this.$watch(
                    () =>
                        JSON.stringify([
                            this.query,
                            this.place,
                            this.category,
                            this.types,
                            this.workplaces,
                            this.sort,
                        ]),
                    () => {
                        ((this.shown = this.pageSize), this.syncUrl());
                    },
                ));
        },
        get matches() {
            const n = this.query.trim().toLowerCase(),
                s = this.place.trim().toLowerCase();
            return this.jobs
                .reduce(
                    (o, a, l) => (
                        (!n || a.text.includes(n)) &&
                            (!s || a.where.includes(s)) &&
                            (!this.category || a.category === this.category) &&
                            (!this.types.length ||
                                this.types.includes(a.type)) &&
                            (!this.workplaces.length ||
                                this.workplaces.includes(a.workplace)) &&
                            o.push(l),
                        o
                    ),
                    [],
                )
                .sort((o, a) =>
                    this.sort === "title"
                        ? this.jobs[o].title.localeCompare(this.jobs[a].title)
                        : this.jobs[a].posted - this.jobs[o].posted,
                );
        },
        get visible() {
            return this.matches.slice(0, this.shown);
        },
        get remaining() {
            return this.matches.length - this.visible.length;
        },
        get summary() {
            const n = this.matches.length;
            return n === 0
                ? "No roles found"
                : `Showing ${this.visible.length} of ${n} ${n === 1 ? "role" : "roles"}`;
        },
        get sidebarCount() {
            return (
                (this.category ? 1 : 0) +
                this.types.length +
                this.workplaces.length
            );
        },
        get activeFilters() {
            const n = [];
            return (
                this.query.trim() &&
                    n.push({ key: "query", label: `“${this.query.trim()}”` }),
                this.place.trim() &&
                    n.push({ key: "place", label: this.place.trim() }),
                this.category &&
                    n.push({
                        key: "category",
                        label: this.categories[this.category],
                    }),
                this.types.forEach((s) =>
                    n.push({ key: "types", value: s, label: s }),
                ),
                this.workplaces.forEach((s) =>
                    n.push({ key: "workplaces", value: s, label: s }),
                ),
                n
            );
        },
        isShown(n) {
            return this.visible.includes(n);
        },
        order(n) {
            return this.matches.indexOf(n);
        },
        loadMore() {
            this.shown += this.pageSize;
        },
        removeFilter(n) {
            n.key === "types" || n.key === "workplaces"
                ? (this[n.key] = this[n.key].filter((s) => s !== n.value))
                : (this[n.key] = "");
        },
        clearAll() {
            ((this.query = ""),
                (this.place = ""),
                (this.category = ""),
                (this.types = []),
                (this.workplaces = []));
        },
        showResults() {
            H_(this.$refs.results);
        },
        syncUrl() {
            const n = new URLSearchParams();
            (this.query.trim() && n.set("q", this.query.trim()),
                this.place.trim() && n.set("place", this.place.trim()),
                this.category && n.set("category", this.category),
                this.types.forEach((i) => n.append("type", i)),
                this.workplaces.forEach((i) => n.append("workplace", i)),
                this.sort !== "newest" && n.set("sort", this.sort));
            const s = n.toString();
            try {
                window.history.replaceState(
                    null,
                    "",
                    window.location.pathname + (s ? `?${s}` : ""),
                );
            } catch {}
        },
    }));
    async function z_() {
        if (document.querySelector("[data-phone-field]"))
            try {
                const { registerInternationalPhone: t } = await pa(async () => {
                    const { registerInternationalPhone: e } =
                        await import("./international-phone-Ke99Mi54.js");
                    return { registerInternationalPhone: e };
                }, []);
                t(we);
            } catch (t) {
                (document
                    .querySelectorAll("[data-phone-field]")
                    .forEach((e) => e.setAttribute("x-ignore", "")),
                    console.error("Unable to load phone formatting.", t));
            }
        (we.start(), F_({ Alpine: we }));
    }
    z_();
    const ju = window.matchMedia("(prefers-color-scheme: dark)"),
        Ei = (t) => {
            ((document.documentElement.dataset.themePreference = t),
                (document.documentElement.dataset.theme =
                    t === "system" ? (ju.matches ? "dark" : "light") : t),
                document
                    .querySelectorAll("[data-theme-control]")
                    .forEach((e) => {
                        e.value = t;
                    }));
        };
    Ei(document.documentElement.dataset.themePreference || "system");
    document.querySelectorAll("[data-theme-control]").forEach((t) => {
        t.addEventListener("change", () => {
            Ei(t.value);
            try {
                localStorage.setItem("marthire-theme", t.value);
            } catch {}
        });
    });
    document.querySelectorAll("[data-theme-toggle]").forEach((t) => {
        t.addEventListener("click", () => {
            const e =
                document.documentElement.dataset.theme === "dark"
                    ? "light"
                    : "dark";
            Ei(e);
            try {
                localStorage.setItem("marthire-theme", e);
            } catch {}
        });
    });
    ju.addEventListener("change", () => {
        document.documentElement.dataset.themePreference === "system" &&
            Ei("system");
    });
});
export default K_();
