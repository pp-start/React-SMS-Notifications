import { Dexie } from "dexie";
import type { Table } from "dexie";

interface User {
  index?: number;
  username?: string;
  userId?: string;
  role?: string;
  token?: string;
  code?: string;
}

interface Token {
  index?: number;
  saved?: boolean;
}

class Database extends Dexie {
  user!: Table<User, number>;
  token!: Table<Token, number>;

  constructor() {
    super("app");

    this.version(1).stores({
      user: "++index",
      token: "++index",
    });
  }
}

export const db: Database = new Database();

db.open().catch(function (err: unknown): void {
  if (err instanceof Error) {
    console.error(err.stack || err.message);
  } else {
    console.error(err);
  }
});
