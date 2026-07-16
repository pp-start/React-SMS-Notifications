import { useEffect, useState, useCallback, useRef } from "react";
import { Axios } from "./Context";
import { useUserContext } from "./Context";
import { db } from "./Db";
import { getMessagingInstance, getToken } from "../firebaseConfig.ts";

export default function Main() {
  type NotificationPermission = {
    status: undefined | boolean;
    message: null | string;
  };

  type FormData = {
    notificationTitle: string;
    notificationBody: string;
  };

  type Modal = {
    show: boolean;
    error: boolean;
  };

  // --- User & logout ---

  const { user, logout } = useUserContext();

  const currentUser = useRef(user);

  useEffect(() => {
    currentUser.current = user;
  }, [user]);

  // Current notification permission status

  const [notificationPermission, setNotificationPermission] =
    useState<NotificationPermission>({ status: undefined, message: null });

  const handlePermission = useCallback((): void => {
    const permission = Notification.permission;

    switch (permission) {
      case "granted":
        setNotificationPermission({ status: true, message: "granted ✅" });

        break;

      case "default":
        setNotificationPermission({
          status: undefined,
          message: "decision pending ⚠️",
        });

        break;

      case "denied":
        setNotificationPermission({
          status: false,
          message: "denied by user 🚫",
        });

        break;

      default:
        setNotificationPermission({ status: false, message: "unknown ⚠️" });
    }
  }, []);

  useEffect(() => {
    handlePermission();
  }, [handlePermission]);

  // Saving device token

  const saveToken = useCallback(
    async (asked: boolean): Promise<void> => {
      const permission = asked
        ? "granted"
        : await Notification.requestPermission();

      if (permission) {
        handlePermission();
      }

      const messaging = await getMessagingInstance();

      db.token.toArray().then(function (result) {
        if (result.length === 0 || result[0].saved === false) {
          const user = currentUser.current;

          if (!messaging) return;

          if (permission === "granted") {
            getToken(messaging, {
              vapidKey: import.meta.env.VITE_APP_VAPID_KEY,
            })
              .then((currentToken) => {
                if (currentToken) {
                  const handler: string = 'notifications';
                  const requestType: string = "saveToken";
                  const userId: string = user!.userId;
                  const firebaseToken: string = currentToken;

                  Axios.post(
                    "classes/handler.php",
                    { handler, requestType, userId, firebaseToken },
                    { timeout: 5000 },
                  )
                    .then(function (response) {
                      if (response.data.success) {
                        db.token.put({ index: 1, saved: true });
                      } else {
                        db.token.put({ index: 1, saved: false });
                      }
                    })
                    .catch((error) => {
                      db.token.put({ index: 1, saved: false });

                      console.warn(error);
                    });
                } else {
                  db.token.put({ index: 1, saved: false });

                  console.log("No FCM token available");
                }
              })
              .catch((err) => console.error("Error getting token", err));
          }
        }
      });
    },
    [handlePermission],
  );

  // Checking if device token has been saved

  useEffect(() => {
    if (Notification.permission === "granted") {
      db.token.toArray().then(function (result) {
        if (result.length > 0) {
          const saved: boolean | undefined = result[0].saved;

          if (!saved) {
            saveToken(true);
          }
        } else {
          saveToken(true);
        }
      });
    }
  }, [saveToken]);

  // Notification test form

  const [form, setForm] = useState<FormData>({
    notificationTitle: "",
    notificationBody: "",
  });

  function formChange(
    event:
      | React.ChangeEvent<HTMLInputElement>
      | React.ChangeEvent<HTMLTextAreaElement>,
  ): void {
    const { name, value } = event.target;

    setForm((prevForm) => {
      return {
        ...prevForm,
        [name]: value,
      };
    });
  }

  // Sending notification

  async function sendNotification() {
    const messaging = await getMessagingInstance();

    if (messaging) {
      getToken(messaging, {
        vapidKey: import.meta.env.VITE_APP_VAPID_KEY,
      })
        .then((currentToken) => {
          if (currentToken) {
            const handler: string = 'notifications';
            const requestType: string = "sendNotification";

            const notificationTitle: string = form.notificationTitle
              ? form.notificationTitle
              : "Default title";

            const notificationBody: string = form.notificationBody
              ? form.notificationBody
              : "Default notification message";

            const firebaseToken: string = currentToken;

            Axios.post(
              "classes/handler.php",
              {
                handler,
                requestType,
                firebaseToken,
                notificationTitle,
                notificationBody,
              },
              { timeout: 5000 },
            )
              .then(function (response) {
                if (!response.data.success) {
                  if (response.data.message) {
                    showModal(response.data.message);
                  } else {
                    showModal(
                      "Unknown error has occured, please try again later.",
                    );
                  }
                }
              })
              .catch((error) => {
                showModal("Unknown error has occured, please try again later.");

                console.warn(error);
              });
          } else {
            showModal(
              "Sorry. Notifications are not available on current browser.",
            );
          }
        })
        .catch((err) =>
          showModal(
            `An error has occured, please try again later. Error message: ${err}`,
          ),
        );
    }
  }

  // Modal

  const [modalMessage, setModalMessage] = useState<null | string>(null);

  const [modal, setModal] = useState<Modal>({
    show: false,
    error: false,
  });

  function showModal(message: string): void {
    setModalMessage(message);
    setModal({ ...modal, show: true, error: true });
  }

  function closeModal(): void {
    setModalMessage(null);

    const closed = Object.fromEntries(
      Object.keys(modal).map((key) => [key, false]),
    ) as Modal;

    setModal(closed);
  }

  return (
    <div id="app-outer-container">
      <div id="app-inner-container">
        <div id="test-container">
          <p id="test-title">Test notifications</p>
          <p className="notification-info">
            Permission status: {notificationPermission.message}
          </p>
          <div id="test-main-wrapper">
            {notificationPermission.status === undefined && (
              <button
                className="test-button button-neutral"
                onClick={() => saveToken(false)}
              >
                Test notifications
              </button>
            )}
            {notificationPermission.status === true && (
              <div id="test-notification-form-wrapper">
                <p className="notification-form-label">Notification title:</p>
                <input
                  className="notification-form"
                  id="notification_title"
                  type="text"
                  onChange={formChange}
                  name="notificationTitle"
                  value={form.notificationTitle}
                />
                <p className="notification-form-label">Notification message:</p>
                <textarea
                  className="notification-form notification-form-textarea"
                  id="notification_body"
                  onChange={formChange}
                  name="notificationBody"
                  value={form.notificationBody}
                />
                <button
                  className="test-button button-ahead"
                  onClick={sendNotification}
                >
                  Send notification
                </button>
              </div>
            )}
            {notificationPermission.status === false && (
              <p className="notification-info">
                Notifications permission needs to be granted to test the app.
              </p>
            )}
          </div>
          <button className="test-button button-exit" onClick={logout}>
            Logout
          </button>
        </div>
      </div>
      {modal.show && (
        <div className="modal-overlay">
          <div
            id="modal"
            className="modal"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="modal-header modal-header-error">
              <h2 className="modal-title">Error</h2>
            </div>
            <div className="modal-body">
              <div className="modal-wrapper">
                <p className="modal-text">{modalMessage}</p>
              </div>
            </div>
            <div className="modal-footer">
              <div className="modal-single-button-wrapper">
                <button
                  className="modal-single-button modal-single-error-button"
                  onClick={closeModal}
                >
                  OK
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
