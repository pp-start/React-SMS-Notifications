import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App";
import { UserContextProvider } from "./components/UserContext";

import * as serviceWorkerRegistration from "./serviceWorkerRegistration.ts";
import "./styles/style.scss";
import "@fontsource/roboto";

export { default as Common } from "./components/Common";
export { default as Offline } from "./components/Offline";
export { default as Error } from "./components/Error";
export { default as Main } from "./components/User.tsx";
export { default as Admin } from "./components/Admin";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <UserContextProvider>
      <App />
    </UserContextProvider>
  </StrictMode>,
);
serviceWorkerRegistration.register();
