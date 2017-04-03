/* global Leafpub, showdown */
/* jshint unused:false */
var Leafpub;

// The Leafpub object
$(function() {
    'use strict';

    Leafpub = {
        // Leafpub metadata
        template: $('meta[name="leafpub:template"]').attr('content'),

        // Returns the admin URL optionally concatenating a path
        adminUrl: function(path) {
            var url = $('meta[name="leafpub:url"]').attr('data-admin');
            return path ?
                url.replace(/\/$/, '') + '/' + path.replace(/^\//, '') :
                url;
        },

        // Shows feedback that gets automatically hidden after a moment. Return a deferred object.
        announce: function(message, options) {
            var defer = $.Deferred(),
                div = $('<div>'),
                transitionIn,
                transitionOut;

            options = $.extend({}, {
                style: 'primary',
                showSpeed: 500,
                hideSpeed: 500,
                hideDelay: 750
            }, options);

            $('.announce').remove();
            $('body').append(
                $(div)
                .addClass('announce announce-' + options.style)
                .text(message)
                .hide()
            );

            $(div).velocity('transition.perspectiveDownIn', options.showSpeed, function() {
                setTimeout(function() {
                    $(div).velocity('transition.perspectiveDownOut', options.hideSpeed, function() {
                        $(div).remove();
                        defer.resolve();
                    });
                }, options.hideDelay);
            });

            return defer;
        },

        // Highlights form errors
        highlightErrors: function(form, fields) {
            // Remove all
            $(form).find('.has-warning').removeClass('has-warning');

            // Highlight fields (if any)
            $.each(fields, function(index, name) {
                $(form).find(':input[name="' + name + '"]')
                .closest('.form-group')
                .addClass('has-warning');
            });
        },

        // Converts markdown to HTML
        markdownToHtml: function(markdown) {
            var converter = new showdown.Converter();

            return converter.makeHtml(markdown);
        },

        // Returns a slug; same as Leafpub::slug()
        slug: function(string) {
            var charmap = {
                "\u00c0":"A","\u00c1":"A","\u00c2":"A","\u00c3":"A","\u00c4":"A","\u00c5":"A","\u00c6":"AE",
                "\u00c7":"C","\u00c8":"E","\u00c9":"E","\u00ca":"E","\u00cb":"E","\u00cc":"I","\u00cd":"I",
                "\u00ce":"I","\u00cf":"I","\u00d0":"D","\u00d1":"N","\u00d2":"O","\u00d3":"o","\u00d4":"O",
                "\u00d5":"O","\u00d6":"O","\u0150":"O","\u00d8":"O","\u00d9":"U","\u00da":"U","\u00db":"U",
                "\u00dc":"U","\u0170":"U","\u00dd":"Y","\u00de":"TH","\u00df":"ss","\u00e0":"a","\u00e1":"a",
                "\u00e2":"a","\u00e3":"a","\u00e4":"a","\u00e5":"a","\u00e6":"ae","\u00e7":"c","\u00e8":"e",
                "\u00e9":"e","\u00ea":"e","\u00eb":"e","\u00ec":"i","\u00ed":"i","\u00ee":"i","\u00ef":"i",
                "\u00f0":"d","\u00f1":"n","\u00f2":"o","\u00f3":"o","\u00f4":"o","\u00f5":"o","\u00f6":"o",
                "\u0151":"o","\u00f8":"o","\u00f9":"u","\u00fa":"u","\u00fb":"u","\u00fc":"u","\u0171":"u",
                "\u00fd":"y","\u00fe":"th","\u00ff":"y","\u00a9":"(c)","\u0391":"A","\u0392":"B","\u0393":"G",
                "\u0394":"D","\u0395":"E","\u0396":"Z","\u0397":"H","\u0398":"8","\u0399":"I","\u039a":"K",
                "\u039b":"L","\u039c":"M","\u039d":"N","\u039e":"3","\u039f":"O","\u03a0":"P","\u03a1":"R",
                "\u03a3":"S","\u03a4":"T","\u03a5":"Y","\u03a6":"F","\u03a7":"X","\u03a8":"PS","\u03a9":"W",
                "\u0386":"A","\u0388":"E","\u038a":"I","\u038c":"O","\u038e":"Y","\u0389":"H","\u038f":"W",
                "\u03aa":"I","\u03ab":"Y","\u03b1":"a","\u03b2":"b","\u03b3":"g","\u03b4":"d","\u03b5":"e",
                "\u03b6":"z","\u03b7":"h","\u03b8":"8","\u03b9":"i","\u03ba":"k","\u03bb":"l","\u03bc":"m",
                "\u03bd":"n","\u03be":"3","\u03bf":"o","\u03c0":"p","\u03c1":"r","\u03c3":"s","\u03c4":"t",
                "\u03c5":"y","\u03c6":"f","\u03c7":"x","\u03c8":"ps","\u03c9":"w","\u03ac":"a","\u03ad":"e",
                "\u03af":"i","\u03cc":"o","\u03cd":"y","\u03ae":"h","\u03ce":"w","\u03c2":"s","\u03ca":"i",
                "\u03b0":"y","\u03cb":"y","\u0390":"i","\u015e":"S","\u0130":"I","\u011e":"G","\u015f":"s",
                "\u0131":"i","\u011f":"g","\u0410":"A","\u0411":"B","\u0412":"V","\u0413":"G","\u0414":"D",
                "\u0415":"E","\u0401":"Yo","\u0416":"Zh","\u0417":"Z","\u0418":"I","\u0419":"J","\u041a":"K",
                "\u041b":"L","\u041c":"M","\u041d":"N","\u041e":"O","\u041f":"P","\u0420":"R","\u0421":"S",
                "\u0422":"T","\u0423":"U","\u0424":"F","\u0425":"H","\u0426":"C","\u0427":"Ch","\u0428":"Sh",
                "\u0429":"Sh","\u042a":"","\u042b":"Y","\u042c":"","\u042d":"E","\u042e":"Yu","\u042f":"Ya",
                "\u0430":"a","\u0431":"b","\u0432":"v","\u0433":"g","\u0434":"d","\u0435":"e","\u0451":"yo",
                "\u0436":"zh","\u0437":"z","\u0438":"i","\u0439":"j","\u043a":"k","\u043b":"l","\u043c":"m",
                "\u043d":"n","\u043e":"o","\u043f":"p","\u0440":"r","\u0441":"s","\u0442":"t","\u0443":"u",
                "\u0444":"f","\u0445":"h","\u0446":"c","\u0447":"ch","\u0448":"sh","\u0449":"sh","\u044a":"",
                "\u044b":"y","\u044c":"","\u044d":"e","\u044e":"yu","\u044f":"ya","\u0404":"Ye","\u0406":"I",
                "\u0407":"Yi","\u0490":"G","\u0454":"ye","\u0456":"i","\u0457":"yi","\u0491":"g","\u010c":"C",
                "\u010e":"D","\u011a":"E","\u0147":"N","\u0158":"R","\u0160":"S","\u0164":"T","\u016e":"U",
                "\u017d":"Z","\u010d":"c","\u010f":"d","\u011b":"e","\u0148":"n","\u0159":"r","\u0161":"s",
                "\u0165":"t","\u016f":"u","\u017e":"z","\u0104":"A","\u0106":"C","\u0118":"e","\u0141":"L",
                "\u0143":"N","\u015a":"S","\u0179":"Z","\u017b":"Z","\u0105":"a","\u0107":"c","\u0119":"e",
                "\u0142":"l","\u0144":"n","\u015b":"s","\u017a":"z","\u017c":"z","\u0100":"A","\u0112":"E",
                "\u0122":"G","\u012a":"i","\u0136":"k","\u013b":"L","\u0145":"N","\u016a":"u","\u0101":"a",
                "\u0113":"e","\u0123":"g","\u012b":"i","\u0137":"k","\u013c":"l","\u0146":"n","\u016b":"u"
            };

            var search =  /À|Á|Â|Ã|Ä|Å|Æ|Ç|È|É|Ê|Ë|Ì|Í|Î|Ï|Ð|Ñ|Ò|Ó|Ô|Õ|Ö|Ő|Ø|Ù|Ú|Û|Ü|Ű|Ý|Þ|ß|à|á|â|ã|ä|å|æ|ç|è|é|ê|ë|ì|í|î|ï|ð|ñ|ò|ó|ô|õ|ö|ő|ø|ù|ú|û|ü|ű|ý|þ|ÿ|©|Α|Β|Γ|Δ|Ε|Ζ|Η|Θ|Ι|Κ|Λ|Μ|Ν|Ξ|Ο|Π|Ρ|Σ|Τ|Υ|Φ|Χ|Ψ|Ω|Ά|Έ|Ί|Ό|Ύ|Ή|Ώ|Ϊ|Ϋ|α|β|γ|δ|ε|ζ|η|θ|ι|κ|λ|μ|ν|ξ|ο|π|ρ|σ|τ|υ|φ|χ|ψ|ω|ά|έ|ί|ό|ύ|ή|ώ|ς|ϊ|ΰ|ϋ|ΐ|Ş|İ|Ğ|ş|ı|ğ|А|Б|В|Г|Д|Е|Ё|Ж|З|И|Й|К|Л|М|Н|О|П|Р|С|Т|У|Ф|Х|Ц|Ч|Ш|Щ|Ъ|Ы|Ь|Э|Ю|Я|а|б|в|г|д|е|ё|ж|з|и|й|к|л|м|н|о|п|р|с|т|у|ф|х|ц|ч|ш|щ|ъ|ы|ь|э|ю|я|Є|І|Ї|Ґ|є|і|ї|ґ|Č|Ď|Ě|Ň|Ř|Š|Ť|Ů|Ž|č|ď|ě|ň|ř|š|ť|ů|ž|Ą|Ć|Ę|Ł|Ń|Ś|Ź|Ż|ą|ć|ę|ł|ń|ś|ź|ż|Ā|Ē|Ģ|Ī|Ķ|Ļ|Ņ|Ū|ā|ē|ģ|ī|ķ|ļ|ņ|ū/g;

        	return string
        		// Convert spaces and underscores to dashes
        		.replace(/(\s|_)/g, '-')
        		// Remove unsafe characters
        		//.replace(/[^A-Z0-9-]/ig, '')
                .replace(search, function(match, p1){
                    return charmap[match];
                })
        		// Remove duplicate dashes
        		.replace(/-+/g, '-')
        		// Remove beginning dashes
        		.replace(/^-+/g, '')
        		// Remove trailing dashes
        		.replace(/-+$/g, '')
        		// Make lowercase
        		.toLowerCase();
        },

        // Uploads files using the FileReader API
        //
        //  options = {
        //      files: event.target.files,
        //      progress: function(percent) {},
        //
        //      // Optional type restriction; valid types: image
        //      accept: 'image',
        //
        //      // Optional image processing data
        //      image: {
        //          // Turns the image into a thumbnail
        //          thumbnail: {
        //              width: 100,
        //              height: 75
        //          }
        //      }
        //  }
        //
        upload: function(options) {
            var defer = $.Deferred(),
                formData = new FormData(),
                key, req, 
                url = 'api/uploads';

            // Set form data
            if (options.accept) formData.append('accept', options.accept);
            if (options.thumbnail) formData.append('thumbnail', JSON.stringify(options.thumbnail));
            if (options.url) url = options.url;

            // Set files
            if(options.files.length) {
                // Multiple files were passed in
                for(key = 0; key < options.files.length; key++) {
                    formData.append('files[]', options.files[key]);
                }
            } else {
                // One file was passed in
                formData.append('files[]', options.files);
            }

            // Send request to upload
            $.ajax({
                url: Leafpub.url(url),
                type: 'POST',
                data: formData,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                xhr: function() {
                    req = $.ajaxSettings.xhr();

                    // Monitor progress
                    if(req.upload && options.progress) {
                        req.upload.addEventListener('progress', function(e) {
                            options.progress.call(req, e.loaded / e.total * 100);
                        }, false);
                    }

                    return req;
                }
            })
            .done(function(res) {
                req = null;
                defer.resolve(res);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                defer.reject();
            });

            // Pass the request back as part of the promise
            return defer.promise({
                request: req
            });
        },

        // Returns the website URL optionally concatenating a path
        url: function(path) {
            var url = $('meta[name="leafpub:url"]').attr('data-base');
            return path ?
                url.replace(/\/$/, '') + '/' + path.replace(/^\//, '') :
                url;
        }
    };
});