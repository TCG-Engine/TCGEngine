"""Run: python -m unittest discover -s DevTools/tests -p test_fighter_transparency.py"""
import importlib.util
from pathlib import Path
import tempfile
import unittest

import cv2
import numpy as np
from PIL import Image

spec = importlib.util.spec_from_file_location("fighter_converter", Path(__file__).parents[1] / "convert-fighter-idle.py")
converter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(converter)


class FighterTransparencyTests(unittest.TestCase):
    def test_preserves_enclosed_dark_details_and_existing_alpha(self):
        rgba = np.zeros((40, 40, 4), dtype=np.uint8)
        rgba[:, :, 3] = 255
        rgba[10:30, 10:30, :3] = [30, 65, 180]
        rgba[15:25, 15:25, :3] = 0
        rgba[12, 12, 3] = 100
        result = np.array(converter.remove_background(Image.fromarray(rgba)))
        self.assertEqual(result[0, 0, 3], 0)
        self.assertEqual(result[20, 20, 3], 255)
        self.assertEqual(result[12, 12, 3], 100)
        self.assertEqual(result[11, 11, 3], 255)

    def test_softens_connected_near_black_only(self):
        rgba = np.full((20, 20, 3), 16, dtype=np.uint8)
        rgba[5:15, 5:15] = [0, 0, 80]
        result = np.array(converter.remove_background(Image.fromarray(rgba)))
        self.assertTrue(0 < result[0, 0, 3] < 255)
        self.assertEqual(result[10, 10, 3], 255)

    def test_video_export_preserves_timing_and_alpha(self):
        with tempfile.TemporaryDirectory(dir=Path(__file__).parent) as directory:
            source = Path(directory) / "idle.avi"
            output = Path(directory) / "idle.webp"
            writer = cv2.VideoWriter(str(source), cv2.VideoWriter_fourcc(*"MJPG"), 30, (64, 64))
            self.assertTrue(writer.isOpened())
            for index in range(30):
                frame = np.zeros((64, 64, 3), dtype=np.uint8)
                frame[16:48, 16 + index % 4:40 + index % 4] = [160, 60, 40]
                writer.write(frame)
            writer.release()
            converter.convert(source, output, height=48)
            with Image.open(output) as image:
                self.assertEqual(image.size, (48, 48))
                self.assertGreater(image.n_frames, 1)
                total = 0
                for index in range(image.n_frames):
                    image.seek(index)
                    image.load()
                    total += image.info["duration"]
                    self.assertEqual(image.convert("RGBA").getpixel((0, 0))[3], 0)
                    self.assertEqual(image.convert("RGBA").getpixel((24, 24))[3], 255)
                self.assertEqual(total, 1000)
                normal_frames = image.n_frames
            fast_output = Path(directory) / "idle-fast.webp"
            converter.convert(source, fast_output, height=48, speed=1.5, loop_count=1)
            with Image.open(fast_output) as image:
                self.assertEqual(image.info['loop'], 1)
                self.assertEqual(image.n_frames, normal_frames)
                total = 0
                for index in range(image.n_frames):
                    image.seek(index)
                    image.load()
                    total += image.info["duration"]
                    self.assertEqual(image.convert("RGBA").getpixel((0, 0))[3], 0)
                self.assertEqual(total, 667)
            for speed in (0, -1, float("nan"), float("inf")):
                with self.assertRaises(ValueError):
                    converter.convert(source, Path(directory) / "invalid.webp", speed=speed)
            with self.assertRaises(FileExistsError):
                converter.convert(source, output)
            with self.assertRaises(ValueError):
                converter.convert(source, source, force=True)


if __name__ == "__main__":
    unittest.main()
