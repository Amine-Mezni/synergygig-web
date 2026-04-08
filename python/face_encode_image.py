"""
Encode a face from a saved image file.
Usage: python face_encode_image.py <image_path>
Output: JSON { "success": true, "encoding": [...314 floats...] }
    or  JSON { "success": false, "error": "..." }
"""
import sys
import os
import json
import warnings
warnings.filterwarnings("ignore")
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"

import cv2

# Add parent python dir so we can import the service
_SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
_PARENT_PYTHON = os.path.join(os.path.dirname(os.path.dirname(_SCRIPT_DIR)), "python")
if _PARENT_PYTHON not in sys.path:
    sys.path.insert(0, _PARENT_PYTHON)

from face_recognition_service import create_face_mesh, detect_and_encode, encoding_to_json


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "No image path provided"}))
        sys.exit(1)

    image_path = sys.argv[1]
    if not os.path.isfile(image_path):
        print(json.dumps({"success": False, "error": f"File not found: {image_path}"}))
        sys.exit(1)

    frame = cv2.imread(image_path)
    if frame is None:
        print(json.dumps({"success": False, "error": "Could not read image"}))
        sys.exit(1)

    landmarker = create_face_mesh(static_mode=True, max_faces=1)
    bboxes, encodings, _ = detect_and_encode(frame, landmarker)
    landmarker.close()

    if not encodings:
        print(json.dumps({"success": False, "error": "No face detected in image"}))
        sys.exit(0)

    if len(encodings) > 1:
        print(json.dumps({"success": False, "error": "Multiple faces detected. Please capture with only your face visible."}))
        sys.exit(0)

    encoding_json = encoding_to_json(encodings[0])
    print(json.dumps({"success": True, "encoding": encoding_json}))


if __name__ == "__main__":
    main()
