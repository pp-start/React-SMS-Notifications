import { Dexie } from 'dexie';

export const db = new Dexie('application');

db.version(1).stores({
    user: '++index',
    token: '++index',
});

db.open().catch(function (err) {
    console.error (err.stack || err);
});