import 'dart:io';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../auth/views/widgets/otp_input_widget.dart';
import 'signature_pad.dart';

/// Collects proof of delivery according to [proofType]: otp | photo | signature.
/// Calls [onSubmit] and pops with true on success.
class ProofCollectionSheet extends StatefulWidget {
  final String proofType;
  final Future<bool> Function({String? otp, File? photo, String? signature})
      onSubmit;

  const ProofCollectionSheet({
    super.key,
    required this.proofType,
    required this.onSubmit,
  });

  @override
  State<ProofCollectionSheet> createState() => _ProofCollectionSheetState();
}

class _ProofCollectionSheetState extends State<ProofCollectionSheet> {
  final _otpController = TextEditingController();
  final _picker = ImagePicker();
  File? _photo;
  String? _signature;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _takePhoto() async {
    final x = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 70,
      maxWidth: 1280,
    );
    if (x != null) setState(() => _photo = File(x.path));
  }

  bool _validate() {
    switch (widget.proofType) {
      case 'otp':
        if (_otpController.text.length != AppConstants.rideOtpLength) {
          _error = 'parcel.enter_receiver_otp'.tr();
          return false;
        }
        return true;
      case 'photo':
        if (_photo == null) {
          _error = 'parcel.take_delivery_photo'.tr();
          return false;
        }
        return true;
      case 'signature':
        if (_signature == null) {
          _error = 'parcel.capture_receiver_signature'.tr();
          return false;
        }
        return true;
      default:
        return true;
    }
  }

  Future<void> _submit() async {
    setState(() => _error = null);
    if (!_validate()) {
      setState(() {});
      return;
    }
    setState(() => _loading = true);
    final ok = await widget.onSubmit(
      otp: widget.proofType == 'otp' ? _otpController.text : null,
      photo: widget.proofType == 'photo' ? _photo : null,
      signature: widget.proofType == 'signature' ? _signature : null,
    );
    if (!mounted) return;
    setState(() => _loading = false);
    if (ok) {
      Navigator.pop(context, true);
    } else {
      setState(() => _error = 'parcel.could_not_verify_proof'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(_title(),
              style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.onSurface)),
          const SizedBox(height: 16),
          _body(),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: AppColors.danger)),
          ],
          const SizedBox(height: 20),
          CustomButton(
            label: 'parcel.confirm_delivery'.tr(),
            loading: _loading,
            onPressed: _submit,
          ),
        ],
      ),
      ),
    );
  }

  String _title() {
    switch (widget.proofType) {
      case 'otp':
        return 'parcel.receiver_otp'.tr();
      case 'photo':
        return 'parcel.delivery_photo'.tr();
      case 'signature':
        return 'parcel.receiver_signature'.tr();
      default:
        return 'parcel.proof_of_delivery'.tr();
    }
  }

  Widget _body() {
    switch (widget.proofType) {
      case 'otp':
        return Center(
          child: OtpInputWidget(
              controller: _otpController, length: AppConstants.rideOtpLength),
        );
      case 'photo':
        return GestureDetector(
          onTap: _takePhoto,
          child: Container(
            height: 160,
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Theme.of(context).dividerColor),
            ),
            child: _photo == null
                ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.camera_alt_outlined,
                          size: 36, color: Theme.of(context).hintColor),
                      const SizedBox(height: 8),
                      Text('parcel.tap_to_take_photo'.tr(),
                          style:
                              TextStyle(color: Theme.of(context).hintColor)),
                    ],
                  )
                : ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: Image.file(_photo!, fit: BoxFit.cover),
                  ),
          ),
        );
      case 'signature':
        return SignaturePad(
          onSaved: (b64) {
            setState(() => _signature = b64);
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text('parcel.signature_captured'.tr())),
            );
          },
        );
      default:
        return const SizedBox.shrink();
    }
  }
}
