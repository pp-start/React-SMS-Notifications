import React, { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import UserContextProvider from './components/UserContext';
import * as serviceWorkerRegistration from './serviceWorkerRegistration';
import './styles/style.scss';
import "@fontsource/roboto";

export { default as Error } from './components/Error';
export { default as Common } from './components/Common';
export { default as Admin } from './components/Admin';
export { default as Main } from './components/Main';

const rootElement = document.getElementById("root");

const root = createRoot(rootElement);

root.render(
  <StrictMode>
    <UserContextProvider>
      <App />
    </UserContextProvider>
  </StrictMode>
);serviceWorkerRegistration.register();