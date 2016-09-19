/**
 * BittyPHP/Env (for node.js)
 *
 * Licensed under The MIT License
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
module.exports = (function(_fs, _path) {
    var FILTER_RETRY_MAX = 5,
    TARGET_FILE = null,
    FILTER_RETRY = 0,
    FILES = {},
    STORAGE = {};


    var Main = function(name) {
        var len = arguments.length;
        if (!arguments.length) {
            return Main.all();
        } else if (1 === len) {
            if ('string' === typeof name) {
                return Main.get(name);
            } else {
                Main.file(name);
            }
        } else {
            Main.file(arguments);
        }
    };

    var LoadFile = function() {
        var FILES = [];
        if (1 === arguments.length) {
            if ('string' === typeof arguments[0]) {
                var path = arguments[0];
                if ('.' === path[0]) {
                    path = GetBaseDir() + '/' + path;
                }

                try {
                    var file = _fs.realpathSync(path);
                    FILES.push(file);
                } catch (e) {}
            } else if('object' === typeof arguments[0]) {
                var arg = arguments[0];
                for (var i = 0, l = arg.length; i < l; i++) {
                    var res = LoadFile(arg[i]);
                    if (res && res.length) {
                        for (var j = 0, jl = res.length; j < jl; j++) {
                            FILES.push(res[j]);
                        }
                    }
                }
            }
        } else {
            var args = arguments;
            for (var i = 0, l = args.length; i < l; i++) {
                var res = LoadFile(args[i]);
                if (res && res.length) {
                    for (var j = 0, jl = res.length; j < jl; j++) {
                        FILES.push(res[j]);
                    }
                }
            }
        }

        return FILES;
    };

    var GetBaseDir = function() {
        var dir = __dirname;
        if (module && module.parent && module.parent.filename) {
            dir = _path.dirname(module.parent.filename);
        }
        return dir;
    };

    var GetFixedPathValue = function (data)
    {
        if (
            'string' === typeof data
            && (data.indexOf('/') !== -1 || data.indexOf('\\') !== -1)
            && (_fs.statSync(data).isDirectory() || _fs.statSync(data).isFile())
        ) {
            data = _fs.realpathSync(data);
        } else if ('object' === typeof data && data instanceof Object) {
            for (var key in data) {
                data[key] = GetFixedPathValue(data[key]);
            }
        }
        return data;
    }

    var Filter = function (value, source) {
        source = ('object' === typeof source && source instanceof Object) ? source : {};
        if ('string' === typeof value) {
            var matches = value.match(/(\{[\w\-\.]+\})/gm);
            if (!matches) {
                return value;
            }

            var replaces = {};
            for (var i = 0, l = matches.length; i < l; i++) {
                var name = matches[i].replace(/^\{|\}$/g, '');
                var match = matches[i];
                if (name.indexOf('.') !== -1) {
                    var src = source;
                    var explode = name.split('.');
                    for (var j = 0, jl = explode.length; j < jl; j++) {
                        if (src.hasOwnProperty(explode[j])) {
                            src = explode[j];
                        } else {
                            src = null;
                            break;
                        }
                    }

                    if ('object' === typeof src && src instanceof Object) {
                        replaces[match] = src;
                    }
                } else {
                    var upper = name.toUpperCase();
                    if ('__DIR__' === upper || '__DIRNAME' === upper) {
                        if (TARGET_FILE) {
                            replaces[match] = _path.dirname(TARGET_FILE);
                        }
                    } else if ('__FILE__' === upper || '__FILENAME' === upper) {
                        if (TARGET_FILE) {
                            replaces[match] = TARGET_FILE;
                        }
                    } else {
                        var val = Main.get(name);
                        if (val) {
                            replaces[match] = val;
                        }
                    }
                }

                if (Object.keys(replaces).length) {
                    for (var key in replaces) {
                        value = value.replace(key, replaces[key]);
                    }

                    if (/(\{[\w\-\.]+\})/m.test(value)) {
                        FILTER_RETRY++;
                    }
                }
            }

            return value;
        } else if ('object' === typeof value && value instanceof Object) {
            for (var key in value) {
                value[key] = Filter(value[key], value);
            }
        }
        return value;
    };

    var FilterRecursive = function(source) {
        source = Filter(source);
        if (FILTER_RETRY > 0 && FILTER_RETRY <= FILTER_RETRY_MAX) {
            FILTER_RETRY = false;
            return FilterRecursive(source);
        }
        FILTER_RETRY = 0;
        return source;
    };

    Main.file = function() {
        var FILES = LoadFile.apply(this, arguments);
        if (!FILES.length) {
            return;
        }

        var loaded = false;
        for (var i = 0, l = FILES.length; i < l; i++) {
            var file = FILES[i];

            if (FILES[file]) {
                continue;
            }

            var datas = require(FILES[i]);
            if (!datas instanceof Object) {
                continue;
            }

            TARGET_FILE = file;
            FILES[file] = datas;
            datas = FilterRecursive(datas);
            for (var key in datas) {
                STORAGE[key] = datas[key];
            }

            STORAGE = FilterRecursive(STORAGE, datas);
            STORAGE = GetFixedPathValue(STORAGE);

            loaded = true;
        }

        TARGET_FILE = null;

        if (loaded) {
            STORAGE = FilterRecursive(STORAGE);
        }

        return true;
    };

    Main.get = function(name) {
        if ('string' !== typeof name) {
            return;
        }

        if (STORAGE.hasOwnProperty(name)) {
            return STORAGE[name];
        } else if (process && process.env && process.env.hasOwnProperty(name)) {
            return process.env[name];
        }
    };

    Main.all = function(name) {
        return STORAGE;
    };

    return Main;
})(require('fs'), require('path'));
