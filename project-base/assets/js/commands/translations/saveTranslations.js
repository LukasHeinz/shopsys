const fs = require('fs');
const PO = require('pofile');
const fileWalker = require('./fileWalker');
const findLang = require('./findLang');

function saveTranslations (translations, dirWithJsFiles, dirWithTranslations, outputDirForExportedTranslations) {

    fileWalker(dirWithTranslations, (walkErr, filePaths) => {
        if (walkErr) {
            console.log(walkErr);
        }

        const promises = filePaths.map(filePath => {

            if (filePath.match(/(\w*)\.po$/) === null) {
                return;
            }

            const lang = findLang(filePath);
            return new Promise((resolve, reject) => {
                PO.load(filePath, (loadErr, po) => {
                    if (loadErr) {
                        console.log(loadErr);
                    }

                    const translated = [];
                    po.items
                        .filter(item => translations.includes(item.msgid))
                        .forEach(item => {
                            translated.push({
                                msgid: item.msgid,
                                msgstr: item.msgstr[0]
                            });
                        });
                    resolve({ lang, translated });
                });
            });
        });

        Promise.all(promises).then(value => {
            const allTranslations = {};
            value.forEach(translatedObject => {
                if (!allTranslations[translatedObject.lang]) {
                    allTranslations[translatedObject.lang] = [];
                }

                allTranslations[translatedObject.lang] = allTranslations[translatedObject.lang].concat(translatedObject.translated);
            });

            fs.writeFile(outputDirForExportedTranslations + 'translations.json', JSON.stringify(allTranslations), (writeErr) => {
                if (writeErr) {
                    return console.log(writeErr);
                }
            });
        });
    });
}

module.exports = saveTranslations;
