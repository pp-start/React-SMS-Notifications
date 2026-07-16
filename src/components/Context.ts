import { createContext, useContext } from "react";

import axios, { type AxiosInstance } from "axios";

export const UserContext: React.Context<UserContextType | undefined> =
  createContext<UserContextType | undefined>(undefined);

export const isLocalhost: boolean = Boolean(
  window.location.hostname === "localhost" ||
  window.location.hostname === "[::1]" ||
  window.location.hostname.match(
    /^127(?:\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}$/,
  ) ||
  window.location.hostname.match(
    /^192(?:\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}$/,
  ),
);

export function useUserContext(): UserContextType {
  const context: UserContextType | undefined = useContext(UserContext);

  if (!context) {
    throw new Error(
      "useUserContext must be used within a UserContext.Provider",
    );
  }

  return context;
}

const localPath: string = import.meta.env.VITE_APP_PATH + "/";

export const Axios: AxiosInstance = axios.create({
  baseURL: isLocalhost ? localPath : "php/",
});
